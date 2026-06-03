<?php

namespace App\Services\Dgii;

use App\Models\ReceivedInvoice;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AcecfService
{
    protected XmlSignatureService $signatureService;
    protected DgiiAuthService $authService;

    public function __construct(XmlSignatureService $signatureService, DgiiAuthService $authService)
    {
        $this->signatureService = $signatureService;
        $this->authService = $authService;
    }

    /**
     * Send a commercial approval (ACECF) for a received invoice.
     *
     * @param ReceivedInvoice $invoice The received invoice to approve/reject
     * @param int $estado 1 = Approved, 2 = Rejected
     * @param string|null $rejectionReason Required when $estado = 2
     * @return array ['success' => bool, 'dgii_response' => string, 'emisor_response' => string, 'errors' => string|null]
     */
    public function sendAprobacionComercial(ReceivedInvoice $invoice, int $estado, ?string $rejectionReason = null): array
    {
        $settings = Setting::getAll();
        $rncComprador = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
        $env = $settings['dgii_env'] ?? 'testing';

        // Certificate
        $certPath = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
        $certPass = $settings['dgii_certificate_password'] ?? '';

        if (!file_exists($certPath)) {
            return ['success' => false, 'dgii_response' => '', 'emisor_response' => '', 'errors' => 'Certificado digital no encontrado'];
        }

        // Build ACECF XML (per official XSD — no namespace)
        $acecfXml = $this->buildAcecfXml(
            $invoice->rnc_emisor,
            $invoice->encf,
            $invoice->fecha_emision->format('d-m-Y'),
            number_format((float)$invoice->monto_total, 2, '.', ''),
            $rncComprador,
            $estado,
            $rejectionReason
        );

        // Sign
        try {
            $signedXml = $this->signatureService->signXml($acecfXml, $certPath, $certPass);
        } catch (Exception $e) {
            Log::error("[AcecfService] Error firmando ACECF: " . $e->getMessage());
            return ['success' => false, 'dgii_response' => '', 'emisor_response' => '', 'errors' => 'Error al firmar ACECF: ' . $e->getMessage()];
        }

        // Authenticate
        try {
            $token = $this->authService->getValidToken($settings);
        } catch (Exception $e) {
            Log::error("[AcecfService] Error autenticando: " . $e->getMessage());
            return ['success' => false, 'dgii_response' => '', 'emisor_response' => '', 'errors' => 'Error de autenticación DGII: ' . $e->getMessage()];
        }

        // Send to DGII
        $dgiiResponse = $this->sendToDgii($signedXml, $token, $rncComprador, $invoice->encf, $env);

        // Send to emisor (best effort — don't fail if emisor URL not available)
        $emisorResponse = $this->sendToEmisor($signedXml, $token, $invoice->rnc_emisor, $settings);

        // Update invoice record
        $invoice->update([
            'approval_status' => $estado === 1 ? 'approved' : 'rejected',
            'rejection_reason' => $estado === 2 ? $rejectionReason : null,
            'approved_at' => now(),
            'acecf_sent_to_dgii' => $dgiiResponse['success'],
            'acecf_sent_to_emisor' => $emisorResponse['success'],
            'dgii_acecf_response' => $dgiiResponse['body'] ?? '',
            'emisor_acecf_response' => $emisorResponse['body'] ?? '',
            'acecf_sent_at' => now(),
        ]);

        $overallSuccess = $dgiiResponse['success'];
        $errors = $dgiiResponse['success'] ? null : ($dgiiResponse['errors'] ?? 'Error enviando a DGII');

        Log::info("[AcecfService] ACECF {$invoice->encf} — DGII: " . ($dgiiResponse['success'] ? 'OK' : 'FAIL') . " | Emisor: " . ($emisorResponse['success'] ? 'OK' : 'N/A'));

        return [
            'success' => $overallSuccess,
            'dgii_response' => $dgiiResponse['body'] ?? '',
            'emisor_response' => $emisorResponse['body'] ?? '',
            'errors' => $errors,
        ];
    }

    /**
     * Build ACECF XML per official XSD (ACECF v.1.0.xsd — no namespace).
     */
    public function buildAcecfXml(
        string $rncEmisor,
        string $encf,
        string $fechaEmision,
        string $montoTotal,
        string $rncComprador,
        int $estado,
        ?string $rejectionReason = null
    ): string {
        $fechaAprobacion = date('d-m-Y H:i:s');
        $detalleTag = '';
        if ($estado === 2 && !empty($rejectionReason)) {
            $detalleTag = "\n    <DetalleMotivoRechazo>" . htmlspecialchars($rejectionReason) . "</DetalleMotivoRechazo>";
        }

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<ACECF>
  <DetalleAprobacionComercial>
    <Version>1.0</Version>
    <RNCEmisor>{$rncEmisor}</RNCEmisor>
    <eNCF>{$encf}</eNCF>
    <FechaEmision>{$fechaEmision}</FechaEmision>
    <MontoTotal>{$montoTotal}</MontoTotal>
    <RNCComprador>{$rncComprador}</RNCComprador>
    <Estado>{$estado}</Estado>{$detalleTag}
    <FechaHoraAprobacionComercial>{$fechaAprobacion}</FechaHoraAprobacionComercial>
  </DetalleAprobacionComercial>
</ACECF>
XML;
    }

    /**
     * Send signed ACECF to DGII endpoint.
     */
    private function sendToDgii(string $signedXml, string $token, string $rncComprador, string $encf, string $env): array
    {
        $envPath = match($env) {
            'production' => 'ecf',
            'certification' => 'certecf',
            default => 'testecf',
        };
        $baseUrl = "https://ecf.dgii.gov.do/{$envPath}";
        $endpoint = "{$baseUrl}/aprobacioncomercial/api/aprobacioncomercial";
        $filename = "{$rncComprador}{$encf}.xml";

        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->attach('xml', $signedXml, $filename, ['Content-Type' => 'text/xml'])
                ->post($endpoint);

            return [
                'success' => $response->successful(),
                'body' => $response->body(),
                'errors' => $response->successful() ? null : "HTTP {$response->status()}: {$response->body()}",
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'body' => $e->getMessage(),
                'errors' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send signed ACECF to the original emisor's aprobacion comercial URL.
     * Best effort — if we don't have their URL, we skip.
     */
    private function sendToEmisor(string $signedXml, string $token, string $rncEmisor, array $settings): array
    {
        // In production, we would look up the emisor's URL via the DGII directory service
        // For now, this is a best-effort attempt
        $env = $settings['dgii_env'] ?? 'testing';

        try {
            // Query DGII directory for the emisor's service URLs
            $envPath = match($env) {
                'production' => 'ecf',
                'certification' => 'certecf',
                default => 'testecf',
            };
            $directoryUrl = "https://ecf.dgii.gov.do/{$envPath}/consultadirectorio/api/consultadirectorio?rnc={$rncEmisor}";

            $dirResponse = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->get($directoryUrl);

            if (!$dirResponse->successful()) {
                Log::warning("[AcecfService] No se pudo consultar directorio para RNC {$rncEmisor}");
                return ['success' => false, 'body' => 'Directorio no disponible'];
            }

            $dirData = $dirResponse->json();
            $aprobacionUrl = $dirData['urlAprobacionComercial'] ?? $dirData['UrlAprobacionComercial'] ?? null;

            if (empty($aprobacionUrl)) {
                Log::info("[AcecfService] Emisor {$rncEmisor} no tiene URL de aprobación comercial");
                return ['success' => false, 'body' => 'Emisor no tiene URL de aprobación'];
            }

            // Send ACECF to emisor
            $emisorResponse = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    'Accept' => '*/*',
                    'Content-Type' => 'multipart/form-data',
                ])
                ->attach('xml', $signedXml, 'acecf.xml', ['Content-Type' => 'text/xml'])
                ->post($aprobacionUrl);

            return [
                'success' => $emisorResponse->successful(),
                'body' => $emisorResponse->body(),
            ];
        } catch (Exception $e) {
            Log::warning("[AcecfService] Error enviando ACECF a emisor {$rncEmisor}: " . $e->getMessage());
            return ['success' => false, 'body' => $e->getMessage()];
        }
    }
}

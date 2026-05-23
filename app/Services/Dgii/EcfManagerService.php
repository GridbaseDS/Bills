<?php

namespace App\Services\Dgii;

use App\Models\Invoice;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EcfManagerService
{
    protected XmlBuilderService $builderService;
    protected XmlSignatureService $signatureService;
    protected DgiiAuthService $authService;
    protected DgiiApiService $apiService;

    public function __construct(
        XmlBuilderService $builderService,
        XmlSignatureService $signatureService,
        DgiiAuthService $authService,
        DgiiApiService $apiService
    ) {
        $this->builderService = $builderService;
        $this->signatureService = $signatureService;
        $this->authService = $authService;
        $this->apiService = $apiService;
    }

    /**
     * Orquestrates the complete flow for an electronic invoice:
     * 1. Assign eNCF
     * 2. Build XML
     * 3. Sign XML
     * 4. Send to DGII (or save for portal if FC<250k)
     *
     * @param Invoice $invoice
     * @return array
     */
    public function processInvoice(Invoice $invoice): array
    {
        try {
            $invoice->load(['client', 'items']);
            $settings = Setting::getAll();

            // 1. Assign eNCF if not already assigned
            if (empty($invoice->encf)) {
                $invoice->encf = $this->generateAndReserveEncf((int)$invoice->ecf_type, $settings);
                $invoice->save();
            }

            // 2. Generate raw unsigned XML
            Log::info("[EcfManagerService] Generando XML para Factura ID: {$invoice->id}, eNCF: {$invoice->encf}");
            $rawXml = $this->builderService->buildInvoiceXml($invoice, $settings);

            // 3. Sign the XML
            $p12Path = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
            $p12Password = $settings['dgii_certificate_password'] ?? '';

            Log::info("[EcfManagerService] Firmando XML para Factura ID: {$invoice->id}");
            $signedXml = $this->signatureService->signXml($rawXml, $p12Path, $p12Password);

            // Save signed XML locally for legal archiving
            $fileName = "signed_ecf/{$invoice->encf}.xml";
            Storage::put($fileName, $signedXml);

            // Extract security code (first 6 chars of SignatureValue base64)
            $securityCode = $this->signatureService->getSecurityCode($signedXml);

            // Update local status as Signed
            $invoice->update([
                'dgii_status' => 'signed',
                'signed_xml_path' => $fileName,
                'security_code' => $securityCode
            ]);

            // 4. Determine if FC<250k (portal upload, not API)
            $isFcLessThan250k = $invoice->ecf_type == 32 && $invoice->total < 250000;

            if ($isFcLessThan250k) {
                // FC<250k: Generate RFCE summary, sign it, send to fc.dgii.gov.do
                // Then save the original e-CF for portal upload
                $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
                
                // Step A: Build RFCE summary XML
                Log::info("[EcfManagerService] FC<250k detected. Generating RFCE for eNCF: {$invoice->encf}");
                $rfceXml = $this->builderService->buildRfceXml($invoice, $settings);

                // Step B: Sign the RFCE
                $signedRfce = $this->signatureService->signXml($rfceXml, $p12Path, $p12Password);
                Storage::put("signed_rfce/{$invoice->encf}.xml", $signedRfce);

                // Step C: Authenticate and send RFCE to fc.dgii.gov.do
                $token = $this->authService->getValidToken($settings);
                $env = $settings['dgii_env'] ?? 'testing';
                $rfceResult = $this->apiService->submitInvoice($signedRfce, $token, $env, true);

                Log::info("[EcfManagerService] RFCE result: " . json_encode($rfceResult));

                if (!$rfceResult['success']) {
                    // RFCE rejected - mark invoice accordingly
                    $invoice->update([
                        'dgii_status' => 'rejected',
                        'dgii_error_messages' => 'RFCE rechazado: ' . ($rfceResult['errors'] ?? 'Error desconocido'),
                    ]);
                    return [
                        'success' => false,
                        'status' => 'rejected',
                        'track_id' => null,
                        'error' => 'RFCE rechazado: ' . $rfceResult['errors'],
                    ];
                }

                // Step D: RFCE accepted. Save original signed e-CF for portal upload
                $uploadDir = "fc_250k_upload";
                $uploadName = "{$rncEmisor}{$invoice->encf}.xml";
                Storage::put("{$uploadDir}/{$uploadName}", $signedXml);

                $invoice->update([
                    'dgii_status' => 'portal_pending',
                    'dgii_track_id' => $rfceResult['track_id'],
                    'dgii_error_messages' => 'RFCE aceptado. FC<250k lista para subir al portal DGII.',
                ]);

                Log::info("[EcfManagerService] RFCE aceptado. FC guardada para portal: {$uploadName}");

                return [
                    'success' => true,
                    'status' => 'portal_pending',
                    'track_id' => $rfceResult['track_id'],
                    'error' => null
                ];
            }

            // 5. Authenticate and Get Token
            $token = $this->authService->getValidToken($settings);

            // 6. Submit to DGII via API
            $env = $settings['dgii_env'] ?? 'testing';
            $result = $this->apiService->submitInvoice($signedXml, $token, $env, false);

            // 7. Update database based on outcome
            $updateData = [
                'dgii_status' => $result['status'],
                'dgii_track_id' => $result['track_id']
            ];

            if ($result['errors']) {
                $updateData['dgii_error_messages'] = is_array($result['errors']) 
                    ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE) 
                    : $result['errors'];
            } else {
                $updateData['dgii_error_messages'] = null;
            }

            $invoice->update($updateData);

            Log::info("[EcfManagerService] Flujo completado para Factura ID: {$invoice->id}. Estado: {$result['status']}");

            return [
                'success' => $result['success'],
                'status' => $result['status'],
                'track_id' => $result['track_id'],
                'error' => $result['errors']
            ];

        } catch (Exception $e) {
            Log::error("[EcfManagerService] Error procesando factura ID {$invoice->id}: " . $e->getMessage());
            
            $invoice->update([
                'dgii_status' => 'contingency',
                'dgii_error_messages' => 'Excepción local: ' . $e->getMessage()
            ]);

            return [
                'success' => false,
                'status' => 'contingency',
                'track_id' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retry sending a failed/contingency invoice to the DGII.
     */
    public function retryInvoice(Invoice $invoice): array
    {
        if (!in_array($invoice->dgii_status, ['contingency', 'rejected', 'signed'])) {
            return [
                'success' => false,
                'status' => $invoice->dgii_status,
                'track_id' => $invoice->dgii_track_id,
                'error' => "No se puede reintentar en estado: {$invoice->dgii_status}"
            ];
        }

        // If already signed, just re-send
        if ($invoice->signed_xml_path && Storage::exists($invoice->signed_xml_path)) {
            $settings = Setting::getAll();
            $signedXml = Storage::get($invoice->signed_xml_path);
            $token = $this->authService->getValidToken($settings);
            $env = $settings['dgii_env'] ?? 'testing';
            
            $result = $this->apiService->submitInvoice($signedXml, $token, $env, false);

            $invoice->update([
                'dgii_status' => $result['status'],
                'dgii_track_id' => $result['track_id'],
                'dgii_error_messages' => $result['errors'] 
                    ? (is_array($result['errors']) ? json_encode($result['errors'], JSON_UNESCAPED_UNICODE) : $result['errors'])
                    : null
            ]);

            return [
                'success' => $result['success'],
                'status' => $result['status'],
                'track_id' => $result['track_id'],
                'error' => $result['errors']
            ];
        }

        // Re-process from scratch
        return $this->processInvoice($invoice);
    }

    /**
     * Check the status of a pending invoice with the DGII.
     */
    public function checkStatus(Invoice $invoice): array
    {
        if ($invoice->dgii_status !== 'pending' || empty($invoice->dgii_track_id)) {
            return [
                'status' => $invoice->dgii_status,
                'errors' => null
            ];
        }

        $settings = Setting::getAll();
        $token = $this->authService->getValidToken($settings);
        $env = $settings['dgii_env'] ?? 'testing';

        $result = $this->apiService->queryInvoiceStatus($invoice->dgii_track_id, $token, $env);

        if ($result['status'] !== 'pending') {
            $invoice->update([
                'dgii_status' => $result['status'],
                'dgii_error_messages' => $result['errors']
            ]);
        }

        return $result;
    }

    /**
     * Generates the next sequential eNCF and reserves it.
     * Format: 'E' + type (31, 32, 33, 34) + 10 digit sequence
     */
    protected function generateAndReserveEncf(int $type, array $settings): string
    {
        $settingKey = "dgii_next_e_ncf_{$type}";
        $nextNum = (int)($settings[$settingKey] ?? 1);

        $encf = 'E' . $type . str_pad((string)$nextNum, 10, '0', STR_PAD_LEFT);

        // Ensure uniqueness
        while (Invoice::where('encf', $encf)->exists()) {
            $nextNum++;
            $encf = 'E' . $type . str_pad((string)$nextNum, 10, '0', STR_PAD_LEFT);
        }

        // Reserve by updating DB
        $settingModel = Setting::where('setting_key', $settingKey)->first();
        if ($settingModel) {
            $settingModel->update(['setting_value' => $nextNum + 1]);
        } else {
            Setting::create([
                'setting_key' => $settingKey,
                'setting_value' => $nextNum + 1,
                'setting_group' => 'dgii'
            ]);
        }

        return $encf;
    }
}

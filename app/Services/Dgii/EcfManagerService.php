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
     * Orquestrates the complete flow for generating, signing, and transmitting an electronic invoice.
     *
     * @param Invoice $invoice Laravel Invoice model.
     * @return array Status of the operation.
     */
    public function processInvoice(Invoice $invoice): array
    {
        try {
            $invoice->load(['client', 'items']);
            $settings = Setting::getAll();

            // 1. Assign e-NCF if not already assigned
            if (empty($invoice->encf)) {
                $invoice->encf = $this->generateAndReserveEncf((int)$invoice->ecf_type, $settings);
                $invoice->save();
            }

            // 2. Generate raw unsigned XML
            Log::info("[EcfManagerService] Generando XML para Factura ID: {$invoice->id}, e-NCF: {$invoice->encf}");
            $rawXml = $this->builderService->buildInvoiceXml($invoice, $settings);

            // 3. Sign the XML
            $p12Path = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
            $p12Password = $settings['dgii_certificate_password'] ?? '';

            Log::info("[EcfManagerService] Firmando XML para Factura ID: {$invoice->id}");
            $signedXml = $this->signatureService->signXml($rawXml, $p12Path, $p12Password);

            // Save signed XML locally for legal archiving and contingency audits
            $fileName = "signed_ecf/{$invoice->encf}.xml";
            Storage::put($fileName, $signedXml);
            $signedXmlPath = Storage::path($fileName);

            // Extract security code (for QR code)
            $securityCode = $this->signatureService->getSecurityCode($signedXml);

            // Update local status as Signed
            $invoice->update([
                'dgii_status' => 'signed',
                'signed_xml_path' => $fileName,
                'security_code' => $securityCode
            ]);

            // 4. Authenticate and Get Token
            $token = $this->authService->getValidToken($settings);

            // 5. Submit to DGII
            $env = $settings['dgii_env'] ?? 'testing';
            $result = $this->apiService->submitInvoice($signedXml, $token, $env);

            // 6. Update database based on outcome
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

            Log::info("[EcfManagerService] Flujo completado para Factura ID: {$invoice->id}. Estado DGII final: {$result['status']}");

            return [
                'success' => $result['success'],
                'status' => $result['status'],
                'track_id' => $result['track_id'],
                'error' => $result['errors']
            ];

        } catch (Exception $e) {
            Log::error("[EcfManagerService] Error crítico procesando factura ID {$invoice->id}: " . $e->getMessage());
            
            // Put invoice in contingency state if transmission fails unexpectedly
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
     * Generates the next sequential eNCF, saves it to database settings, and returns it.
     * e-NCF Format: 'E' + type (31, 32) + 10 digit sequence (e.g. E310000000001)
     *
     * @param int $type e-CF type (31, 32, etc.)
     * @param array $settings Current settings map.
     * @return string Valid E-NCF sequence.
     * @throws Exception
     */
    protected function generateAndReserveEncf(int $type, array $settings): string
    {
        $settingKey = "dgii_next_e_ncf_{$type}";
        $nextNum = (int)($settings[$settingKey] ?? 1);

        $encf = 'E' . $type . str_pad((string)$nextNum, 10, '0', STR_PAD_LEFT);

        // Ensure uniqueness check in DB
        while (Invoice::where('encf', $encf)->exists()) {
            $nextNum++;
            $encf = 'E' . $type . str_pad((string)$nextNum, 10, '0', STR_PAD_LEFT);
        }

        // Reserve it by updating DB settings
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

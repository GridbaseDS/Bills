<?php

namespace App\Services\Dgii;

use App\Models\DgiiLog;
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
        $startTime = microtime(true);

        try {
            $invoice->load(['client', 'items']);
            $settings = Setting::getAll();

            DgiiLog::logStep('process_start', "Iniciando procesamiento de factura", $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'context' => [
                    'invoice_number' => $invoice->invoice_number,
                    'client' => $invoice->client->company_name ?? $invoice->client->contact_name ?? 'N/A',
                    'client_tax_id' => $invoice->client->tax_id ?? 'EMPTY',
                    'total' => $invoice->total,
                    'subtotal' => $invoice->subtotal,
                    'tax_amount' => $invoice->tax_amount,
                    'ecf_type' => $invoice->ecf_type,
                    'is_ecf' => $invoice->is_ecf,
                    'items_count' => $invoice->items->count(),
                    'items' => $invoice->items->map(fn($i) => [
                        'desc' => $i->description,
                        'qty' => $i->quantity,
                        'price' => $i->unit_price,
                        'amount' => $i->amount,
                    ])->toArray(),
                ],
            ]);

            // 1. Assign eNCF if not already assigned
            if (empty($invoice->encf)) {
                $invoice->encf = $this->generateAndReserveEncf((int)$invoice->ecf_type, $settings);
                $invoice->save();

                DgiiLog::logStep('encf_assigned', "eNCF asignado: {$invoice->encf}", $invoice->id, $invoice->encf, $invoice->ecf_type, [
                    'context' => ['encf' => $invoice->encf],
                ]);
            } else {
                DgiiLog::logStep('encf_reused', "eNCF ya existente: {$invoice->encf}", $invoice->id, $invoice->encf, $invoice->ecf_type);
            }

            // 2. Generate raw unsigned XML
            Log::info("[EcfManagerService] Generando XML para Factura ID: {$invoice->id}, eNCF: {$invoice->encf}");
            $rawXml = $this->builderService->buildInvoiceXml($invoice, $settings);

            // Extract key fields from generated XML for audit
            $xmlAudit = $this->extractXmlAuditFields($rawXml);
            DgiiLog::logStep('xml_built', "XML sin firmar generado ({$xmlAudit['xml_length']} bytes)", $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'context' => $xmlAudit,
            ]);

            // 3. Sign the XML
            $p12Path = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
            $p12Password = $settings['dgii_certificate_password'] ?? '';

            DgiiLog::logStep('sign_start', "Firmando XML. Certificado: " . basename($p12Path) . " (existe: " . (file_exists($p12Path) ? 'SÍ' : 'NO') . ")", $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'context' => [
                    'cert_path' => basename($p12Path),
                    'cert_exists' => file_exists($p12Path),
                    'cert_size' => file_exists($p12Path) ? filesize($p12Path) : 0,
                ],
            ]);

            Log::info("[EcfManagerService] Firmando XML para Factura ID: {$invoice->id}");
            $signedXml = $this->signatureService->signXml($rawXml, $p12Path, $p12Password);

            // Verify signature was actually added
            $hasSignature = strpos($signedXml, '<SignatureValue>') !== false;
            $signatureAudit = $this->extractSignatureAudit($signedXml);

            DgiiLog::logStep('xml_signed', "XML firmado ({$signatureAudit['signed_xml_length']} bytes). Firma presente: " . ($hasSignature ? 'SÍ' : 'NO'), $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'level' => $hasSignature ? 'info' : 'critical',
                'context' => $signatureAudit,
            ]);

            if (!$hasSignature) {
                throw new Exception("CRITICAL: El XML firmado NO contiene <SignatureValue>. La firma digital falló silenciosamente.");
            }

            // Save signed XML locally for legal archiving
            $fileName = "signed_ecf/{$invoice->encf}.xml";
            Storage::put($fileName, $signedXml);

            // Extract security code (first 6 chars of SignatureValue base64)
            $securityCode = $this->signatureService->getSecurityCode($signedXml);

            // Update local status as Signed
            $invoice->update([
                'dgii_status' => 'signed',
                'signed_xml_path' => $fileName,
                'security_code' => $securityCode,
                'signed_at' => now()
            ]);

            DgiiLog::logStep('signed_saved', "XML firmado guardado. Security Code: {$securityCode}", $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'context' => [
                    'file_path' => $fileName,
                    'security_code' => $securityCode,
                    'signed_at' => now()->toDateTimeString(),
                    'fecha_hora_firma_xml' => $signatureAudit['fecha_hora_firma'] ?? 'N/A',
                ],
            ]);

            // 4. Determine if FC<250k (portal upload, not API)
            $isFcLessThan250k = $invoice->ecf_type == 32 && $invoice->total < 250000;

            if ($isFcLessThan250k) {
                return $this->handleFcLessThan250k($invoice, $signedXml, $p12Path, $p12Password, $settings);
            }

            // 5. Authenticate and Get Token
            $token = $this->authService->getValidToken($settings, $invoice->id, $invoice->encf, $invoice->ecf_type);

            DgiiLog::logStep('auth_token_obtained', "Token DGII obtenido (" . strlen($token) . " chars)", $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'context' => [
                    'token_prefix' => substr($token, 0, 30) . '...',
                    'token_length' => strlen($token),
                ],
            ]);

            // 6. Submit to DGII via API
            $env = $settings['dgii_env'] ?? 'testing';
            $result = $this->apiService->submitInvoice($signedXml, $token, $env, false, $invoice->id, $invoice->encf, $invoice->ecf_type);

            // Retry once if DGII returns HTTP 401 Unauthorized (invalid/expired token on DGII's end despite cache)
            if (!$result['success'] && strpos((string)$result['errors'], 'HTTP 401') !== false) {
                Log::warning("[EcfManagerService] Recibido HTTP 401 de DGII. Invalidando token y reintentando...");
                $this->authService->clearToken($settings);
                
                $token = $this->authService->getValidToken($settings, $invoice->id, $invoice->encf, $invoice->ecf_type);
                $result = $this->apiService->submitInvoice($signedXml, $token, $env, false, $invoice->id, $invoice->encf, $invoice->ecf_type);
            }

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

            // 8. POST-SUBMIT VERIFICATION: Check if the QR/ConsultaTimbre will work
            if ($result['success'] && $result['track_id']) {
                $this->verifyPostSubmit($invoice, $signedXml, $settings, $result['track_id']);
            }

            $elapsed = round((microtime(true) - $startTime) * 1000, 1);
            DgiiLog::logStep('process_complete', "Flujo completo en {$elapsed}ms. Estado final: {$result['status']}", $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'dgii_status' => $result['status'],
                'dgii_track_id' => $result['track_id'],
                'dgii_error_messages' => $result['errors'],
                'context' => [
                    'elapsed_ms' => $elapsed,
                    'final_status' => $result['status'],
                    'track_id' => $result['track_id'],
                ],
            ]);

            Log::info("[EcfManagerService] Flujo completado para Factura ID: {$invoice->id}. Estado: {$result['status']}");

            return [
                'success' => $result['success'],
                'status' => $result['status'],
                'track_id' => $result['track_id'],
                'error' => $result['errors']
            ];

        } catch (Exception $e) {
            $elapsed = round((microtime(true) - $startTime) * 1000, 1);

            DgiiLog::logStep('process_error', "EXCEPCIÓN: {$e->getMessage()}", $invoice->id, $invoice->encf ?? null, $invoice->ecf_type ?? null, [
                'level' => 'critical',
                'context' => [
                    'exception_class' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'trace_excerpt' => substr($e->getTraceAsString(), 0, 1000),
                    'elapsed_ms' => $elapsed,
                ],
            ]);

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
     * Handle FC<250k invoices (RFCE flow)
     */
    protected function handleFcLessThan250k(Invoice $invoice, string $signedXml, string $p12Path, string $p12Password, array $settings): array
    {
        $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');

        DgiiLog::logStep('fc250k_detected', "FC<250k detectada. Total: {$invoice->total}. Generando RFCE.", $invoice->id, $invoice->encf, $invoice->ecf_type);

        // Step A: Build RFCE summary XML
        Log::info("[EcfManagerService] FC<250k detected. Generating RFCE for eNCF: {$invoice->encf}");
        $rfceXml = $this->builderService->buildRfceXml($invoice, $settings);

        DgiiLog::logStep('rfce_xml_built', "RFCE XML generado (" . strlen($rfceXml) . " bytes)", $invoice->id, $invoice->encf, $invoice->ecf_type);

        // Step B: Sign the RFCE
        $signedRfce = $this->signatureService->signXml($rfceXml, $p12Path, $p12Password);
        Storage::put("signed_rfce/{$invoice->encf}.xml", $signedRfce);

        DgiiLog::logStep('rfce_xml_signed', "RFCE XML firmado (" . strlen($signedRfce) . " bytes)", $invoice->id, $invoice->encf, $invoice->ecf_type);

        // Step C: Authenticate and send RFCE to fc.dgii.gov.do
        $token = $this->authService->getValidToken($settings, $invoice->id, $invoice->encf, $invoice->ecf_type);
        $env = $settings['dgii_env'] ?? 'testing';
        $rfceResult = $this->apiService->submitInvoice($signedRfce, $token, $env, true, $invoice->id, $invoice->encf, $invoice->ecf_type);

        if (!$rfceResult['success'] && strpos((string)$rfceResult['errors'], 'HTTP 401') !== false) {
            Log::warning("[EcfManagerService] Recibido HTTP 401 de DGII en RFCE. Invalidando token y reintentando...");
            $this->authService->clearToken($settings);
            
            $token = $this->authService->getValidToken($settings, $invoice->id, $invoice->encf, $invoice->ecf_type);
            $rfceResult = $this->apiService->submitInvoice($signedRfce, $token, $env, true, $invoice->id, $invoice->encf, $invoice->ecf_type);
        }

        Log::info("[EcfManagerService] RFCE result: " . json_encode($rfceResult));

        if (!$rfceResult['success']) {
            $invoice->update([
                'dgii_status' => 'rejected',
                'dgii_error_messages' => 'RFCE rechazado: ' . ($rfceResult['errors'] ?? 'Error desconocido'),
            ]);

            DgiiLog::logStep('rfce_rejected', "RFCE RECHAZADO: " . ($rfceResult['errors'] ?? 'Desconocido'), $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'level' => 'error',
                'dgii_status' => 'rejected',
                'dgii_error_messages' => $rfceResult['errors'],
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

        DgiiLog::logStep('rfce_accepted', "RFCE aceptado. FC guardada para portal: {$uploadName}", $invoice->id, $invoice->encf, $invoice->ecf_type, [
            'dgii_status' => 'portal_pending',
            'dgii_track_id' => $rfceResult['track_id'],
        ]);

        Log::info("[EcfManagerService] RFCE aceptado. FC guardada para portal: {$uploadName}");

        return [
            'success' => true,
            'status' => 'portal_pending',
            'track_id' => $rfceResult['track_id'],
            'error' => null
        ];
    }

    /**
     * Post-submit verification: query the trackId status and test ConsultaTimbre QR URL.
     */
    protected function verifyPostSubmit(Invoice $invoice, string $signedXml, array $settings, string $trackId): void
    {
        try {
            // 1. Query track ID status
            $token = $this->authService->getValidToken($settings);
            $env = $settings['dgii_env'] ?? 'testing';
            $statusResult = $this->apiService->queryInvoiceStatus($trackId, $token, $env);

            DgiiLog::logStep('post_verify_status', "Verificación post-envío del trackId. Estado DGII: {$statusResult['status']}", $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'dgii_track_id' => $trackId,
                'dgii_status' => $statusResult['status'],
                'dgii_error_messages' => $statusResult['errors'],
                'context' => [
                    'track_id' => $trackId,
                    'status_from_dgii' => $statusResult['status'],
                    'errors_from_dgii' => $statusResult['errors'],
                ],
            ]);

            // 2. Build and test ConsultaTimbre QR URL
            $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
            $ecfType = (int)$invoice->ecf_type;
            $hasComprador = !in_array($ecfType, [43, 47]);

            // Extract values from signed XML (authoritative source)
            preg_match('/<RNCComprador>([^<]+)<\/RNCComprador>/', $signedXml, $rncMatch);
            preg_match('/<MontoTotal>([^<]+)<\/MontoTotal>/', $signedXml, $montoMatch);
            preg_match('/<FechaEmision>([^<]+)<\/FechaEmision>/', $signedXml, $fechaMatch);
            preg_match('/<FechaHoraFirma>([^<]+)<\/FechaHoraFirma>/', $signedXml, $firmaMatch);

            $rncComprador = $rncMatch[1] ?? '';
            $montoTotal = $montoMatch[1] ?? '';
            $fechaEmision = $fechaMatch[1] ?? '';
            $fechaFirma = $firmaMatch[1] ?? '';
            $securityCode = $invoice->security_code;

            $ecfQrPath = match($env) {
                'production' => 'ecf',
                'certification' => 'certecf',
                default => 'testecf',
            };
            $qrUrl = "https://ecf.dgii.gov.do/{$ecfQrPath}/ConsultaTimbre?"
                . "RncEmisor={$rncEmisor}";
            if ($hasComprador) {
                $qrUrl .= "&RncComprador={$rncComprador}";
            }
            $qrUrl .= "&ENCF={$invoice->encf}"
                . "&FechaEmision={$fechaEmision}"
                . "&MontoTotal={$montoTotal}"
                . "&FechaFirma=" . urlencode($fechaFirma)
                . "&CodigoSeguridad=" . urlencode($securityCode);

            // Test the QR URL
            $qrVerified = false;
            try {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $qrUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_TIMEOUT => 10,
                ]);
                $qrResponse = curl_exec($ch);
                $qrHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $qrVerified = $qrResponse !== false
                    && strpos($qrResponse, 'No fue encontrada') === false
                    && strpos($qrResponse, 'Error') === false;
            } catch (\Throwable $e) {
                // QR verification is best-effort, don't fail the whole process
            }

            DgiiLog::logStep('qr_verify', "Verificación QR ConsultaTimbre: " . ($qrVerified ? 'ENCONTRADA ✓' : 'NO ENCONTRADA ✗ (puede tardar en indexarse)'), $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'level' => $qrVerified ? 'info' : 'warning',
                'qr_verified' => $qrVerified,
                'qr_url' => $qrUrl,
                'context' => [
                    'qr_url' => $qrUrl,
                    'qr_http_code' => $qrHttpCode ?? null,
                    'qr_found' => $qrVerified,
                    'xml_values_used' => [
                        'RncEmisor' => $rncEmisor,
                        'RncComprador' => $rncComprador,
                        'ENCF' => $invoice->encf,
                        'FechaEmision' => $fechaEmision,
                        'MontoTotal' => $montoTotal,
                        'FechaFirma' => $fechaFirma,
                        'CodigoSeguridad' => $securityCode,
                    ],
                    'db_values_comparison' => [
                        'db_total' => number_format((float)$invoice->total, 2, '.', ''),
                        'db_issue_date' => $invoice->issue_date ? date('d-m-Y', strtotime($invoice->issue_date)) : 'NULL',
                        'db_signed_at' => $invoice->signed_at ? date('d-m-Y H:i:s', strtotime($invoice->signed_at)) : 'NULL',
                        'db_security_code' => $invoice->security_code,
                        'match_total' => $montoTotal === number_format((float)$invoice->total, 2, '.', ''),
                        'match_security_code' => $securityCode === $invoice->security_code,
                    ],
                ],
            ]);

        } catch (\Throwable $e) {
            DgiiLog::logStep('post_verify_error', "Error en verificación post-envío: " . $e->getMessage(), $invoice->id, $invoice->encf, $invoice->ecf_type, [
                'level' => 'warning',
            ]);
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

        DgiiLog::logStep('retry_start', "Reintentando factura. Estado anterior: {$invoice->dgii_status}", $invoice->id, $invoice->encf, $invoice->ecf_type, [
            'context' => [
                'previous_status' => $invoice->dgii_status,
                'previous_track_id' => $invoice->dgii_track_id,
                'previous_errors' => $invoice->dgii_error_messages,
            ],
        ]);

        // Clear cached signed XML so processInvoice regenerates from scratch
        // This ensures any XML structure fixes are picked up on retry
        if ($invoice->signed_xml_path && Storage::exists($invoice->signed_xml_path)) {
            Storage::delete($invoice->signed_xml_path);
        }

        // Reset state so processInvoice can re-assign and re-sign
        $invoice->update([
            'dgii_status' => null,
            'signed_xml_path' => null,
            'security_code' => null,
            'dgii_track_id' => null,
            'dgii_error_messages' => null,
        ]);

        // Re-process from scratch (regenerate XML, re-sign, re-send)
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

    /**
     * Extract key audit fields from an unsigned XML for logging.
     */
    protected function extractXmlAuditFields(string $xml): array
    {
        $fields = ['xml_length' => strlen($xml)];
        $tags = ['TipoeCF', 'eNCF', 'RNCEmisor', 'RNCComprador', 'FechaEmision', 'MontoTotal',
                 'MontoGravadoTotal', 'TotalITBIS', 'RazonSocialEmisor', 'RazonSocialComprador',
                 'TipoIngresos', 'IndicadorMontoGravado', 'FechaVencimientoSecuencia'];
        foreach ($tags as $tag) {
            if (preg_match("/<{$tag}>([^<]*)<\/{$tag}>/", $xml, $m)) {
                $fields[$tag] = $m[1];
            }
        }
        // Count items
        $fields['item_count'] = substr_count($xml, '<NumeroLinea>');
        return $fields;
    }

    /**
     * Extract signature-related audit fields from a signed XML.
     */
    protected function extractSignatureAudit(string $signedXml): array
    {
        $audit = [
            'signed_xml_length' => strlen($signedXml),
            'has_signature' => strpos($signedXml, '<SignatureValue>') !== false,
            'has_x509' => strpos($signedXml, '<X509Certificate>') !== false,
        ];

        if (preg_match('/<SignatureValue>([^<]{0,20})/', $signedXml, $m)) {
            $audit['signature_prefix'] = $m[1] . '...';
            $audit['security_code_from_sig'] = substr(trim($m[1]), 0, 6);
        }
        if (preg_match('/<FechaHoraFirma>([^<]+)<\/FechaHoraFirma>/', $signedXml, $m)) {
            $audit['fecha_hora_firma'] = $m[1];
        }
        if (preg_match('/<DigestValue>([^<]+)<\/DigestValue>/', $signedXml, $m)) {
            $audit['digest_value'] = $m[1];
        }

        return $audit;
    }
}

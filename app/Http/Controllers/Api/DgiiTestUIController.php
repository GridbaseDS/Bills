<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Setting;
use App\Services\Dgii\EcfManagerService;
use App\Services\Dgii\XmlBuilderService;
use App\Services\Dgii\XmlSignatureService;
use App\Services\Dgii\DgiiAuthService;
use App\Services\Dgii\DgiiApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\BufferedOutput;

class DgiiTestUIController extends Controller
{
    /**
     * Executes the DGII certification test suite via Artisan.
     */
    public function runTests(Request $request)
    {
        $output = new BufferedOutput();
        
        try {
            $exitCode = Artisan::call('dgii:run-tests', [], $output);
            $textOutput = $output->fetch();
            
            $fc250kFiles = [];
            $dir = storage_path('app/dgii_tests/fc_250k_upload');
            if (is_dir($dir)) {
                foreach (glob("$dir/*.xml") as $file) {
                    $fc250kFiles[] = [
                        'name' => basename($file),
                        'content' => base64_encode(file_get_contents($file)),
                    ];
                }
            }
            
            return response()->json([
                'success' => $exitCode === 0,
                'output' => $textOutput,
                'fc250k_files' => $fc250kFiles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'output' => $output->fetch() . "\nError Fatal: " . $e->getMessage(),
                'fc250k_files' => [],
            ], 500);
        }
    }

    /**
     * Executes the DGII Aprobaciones Comerciales test via Artisan.
     */
    public function runAprobaciones(Request $request)
    {
        $output = new BufferedOutput();
        
        try {
            $exitCode = Artisan::call('dgii:run-aprobaciones', [], $output);
            $textOutput = $output->fetch();
            
            return response()->json([
                'success' => $exitCode === 0,
                'output' => $textOutput,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'output' => $output->fetch() . "\nError Fatal: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Diagnostic: Test the full e-CF flow with a real invoice without sending to DGII.
     * Steps: Load settings → Build XML → Sign XML → Authenticate → (optional) Send
     */
    public function diagnose(
        Request $request,
        XmlBuilderService $builder,
        XmlSignatureService $signer,
        DgiiAuthService $auth,
        DgiiApiService $api
    ) {
        $invoiceId = $request->input('invoice_id');
        $sendForReal = $request->boolean('send', false);
        $log = [];
        $success = true;

        // Step 0: Load settings
        $log[] = ['step' => 'Cargar Configuración', 'status' => 'running'];
        try {
            $settings = Setting::getAll();
            $certPath = $settings['dgii_certificate_path'] ?? '';
            $certPass = $settings['dgii_certificate_password'] ?? '';
            $env = $settings['dgii_env'] ?? 'testing';
            $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
            
            $log[count($log)-1] = [
                'step' => 'Cargar Configuración',
                'status' => 'ok',
                'detail' => "RNC: {$rncEmisor} | Env: {$env} | Cert: {$certPath}"
            ];
        } catch (\Exception $e) {
            $log[count($log)-1] = ['step' => 'Cargar Configuración', 'status' => 'error', 'detail' => $e->getMessage()];
            return response()->json(['success' => false, 'log' => $log]);
        }

        // Step 1: Verify certificate
        $log[] = ['step' => 'Verificar Certificado .p12', 'status' => 'running'];
        $p12Full = storage_path('app/secure/' . $certPath);
        if (!file_exists($p12Full)) {
            $log[count($log)-1] = ['step' => 'Verificar Certificado .p12', 'status' => 'error', 'detail' => "Archivo no encontrado: {$p12Full}"];
            return response()->json(['success' => false, 'log' => $log]);
        }
        $certs = [];
        if (!openssl_pkcs12_read(file_get_contents($p12Full), $certs, $certPass)) {
            $log[count($log)-1] = ['step' => 'Verificar Certificado .p12', 'status' => 'error', 'detail' => 'Contraseña incorrecta o archivo corrupto'];
            return response()->json(['success' => false, 'log' => $log]);
        }
        $certInfo = openssl_x509_parse($certs['cert']);
        $cn = $certInfo['subject']['CN'] ?? 'N/A';
        $validTo = date('Y-m-d', $certInfo['validTo_time_t'] ?? 0);
        $log[count($log)-1] = ['step' => 'Verificar Certificado .p12', 'status' => 'ok', 'detail' => "CN: {$cn} | Válido hasta: {$validTo}"];

        // Step 2: Authenticate with DGII
        $log[] = ['step' => 'Autenticación DGII (Semilla → Token)', 'status' => 'running'];
        try {
            \Illuminate\Support\Facades\Cache::forget("dgii_bearer_token_{$rncEmisor}_{$env}");
            $token = $auth->getValidToken($settings);
            $tokenPreview = substr($token, 0, 20) . '...';
            $log[count($log)-1] = ['step' => 'Autenticación DGII (Semilla → Token)', 'status' => 'ok', 'detail' => "Token: {$tokenPreview}"];
        } catch (\Exception $e) {
            $log[count($log)-1] = ['step' => 'Autenticación DGII (Semilla → Token)', 'status' => 'error', 'detail' => $e->getMessage()];
            return response()->json(['success' => false, 'log' => $log]);
        }

        // Step 3: Load or create test invoice
        $log[] = ['step' => 'Cargar Factura', 'status' => 'running'];
        try {
            if ($invoiceId) {
                $invoice = Invoice::with(['client', 'items'])->findOrFail($invoiceId);
                if (!$invoice->is_ecf) {
                    $log[count($log)-1] = ['step' => 'Cargar Factura', 'status' => 'error', 'detail' => 'La factura no tiene e-CF habilitado'];
                    return response()->json(['success' => false, 'log' => $log]);
                }
            } else {
                // Use the last e-CF invoice or any recent invoice
                $invoice = Invoice::with(['client', 'items'])
                    ->where('is_ecf', 1)
                    ->orderBy('created_at', 'desc')
                    ->first();
                if (!$invoice) {
                    $invoice = Invoice::with(['client', 'items'])->orderBy('created_at', 'desc')->first();
                }
                if (!$invoice) {
                    $log[count($log)-1] = ['step' => 'Cargar Factura', 'status' => 'error', 'detail' => 'No hay facturas en el sistema'];
                    return response()->json(['success' => false, 'log' => $log]);
                }
                // Force e-CF for test
                if (!$invoice->is_ecf) {
                    $invoice->is_ecf = true;
                    $invoice->ecf_type = 31;
                }
            }

            $log[count($log)-1] = [
                'step' => 'Cargar Factura',
                'status' => 'ok',
                'detail' => "#{$invoice->invoice_number} | Cliente: {$invoice->client->company_name} | Total: {$invoice->currency} " . number_format($invoice->total, 2) . " | Tipo: {$invoice->ecf_type}"
            ];
        } catch (\Exception $e) {
            $log[count($log)-1] = ['step' => 'Cargar Factura', 'status' => 'error', 'detail' => $e->getMessage()];
            return response()->json(['success' => false, 'log' => $log]);
        }

        // Step 4: Build XML
        $log[] = ['step' => 'Generar XML e-CF', 'status' => 'running'];
        try {
            // Temporarily assign eNCF if not assigned (don't save to DB for diagnostic)
            $tempEncf = $invoice->encf;
            if (empty($tempEncf)) {
                $type = (int)$invoice->ecf_type;
                $tempEncf = 'E' . $type . '0000099999'; // Dummy sequence for diagnostic
                $invoice->encf = $tempEncf;
            }
            $rawXml = $builder->buildInvoiceXml($invoice, $settings);
            $xmlSize = strlen($rawXml);
            $xmlLines = substr_count($rawXml, "\n") + 1;
            $log[count($log)-1] = ['step' => 'Generar XML e-CF', 'status' => 'ok', 'detail' => "eNCF: {$tempEncf} | {$xmlSize} bytes, {$xmlLines} líneas"];
        } catch (\Exception $e) {
            $log[count($log)-1] = ['step' => 'Generar XML e-CF', 'status' => 'error', 'detail' => $e->getMessage()];
            return response()->json(['success' => false, 'log' => $log]);
        }

        // Step 5: Sign XML
        $log[] = ['step' => 'Firmar XML (XMLDSig RSA-SHA256)', 'status' => 'running'];
        try {
            $signedXml = $signer->signXml($rawXml, $p12Full, $certPass);
            $securityCode = $signer->getSecurityCode($signedXml);
            $signedSize = strlen($signedXml);
            $hasSignature = strpos($signedXml, '<SignatureValue>') !== false;
            $log[count($log)-1] = [
                'step' => 'Firmar XML (XMLDSig RSA-SHA256)',
                'status' => 'ok',
                'detail' => "Firmado: {$signedSize} bytes | Código Seguridad: {$securityCode} | Signature: " . ($hasSignature ? '✅ presente' : '❌ FALTA')
            ];
        } catch (\Exception $e) {
            $log[count($log)-1] = ['step' => 'Firmar XML (XMLDSig RSA-SHA256)', 'status' => 'error', 'detail' => $e->getMessage()];
            return response()->json(['success' => false, 'log' => $log]);
        }

        // Step 6: Validate XML structure
        $log[] = ['step' => 'Validar Estructura XML', 'status' => 'running'];
        $dom = new \DOMDocument();
        $dom->loadXML($rawXml);

        $requiredElements = ['TipoeCF', 'eNCF', 'RNCEmisor', 'FechaEmision', 'MontoTotal', 'FechaHoraFirma'];
        $checks = [];
        $allPresent = true;
        foreach ($requiredElements as $el) {
            $found = $dom->getElementsByTagName($el)->length > 0;
            $checks[] = ($found ? '✅' : '❌') . " {$el}";
            if (!$found) $allPresent = false;
        }

        // Also verify main sections
        $sections = ['Encabezado', 'IdDoc', 'Emisor', 'Totales', 'DetallesItems'];
        foreach ($sections as $sec) {
            $found = $dom->getElementsByTagName($sec)->length > 0;
            $checks[] = ($found ? '✅' : '❌') . " {$sec}";
            if (!$found) $allPresent = false;
        }

        $log[count($log)-1] = [
            'step' => 'Validar Estructura XML',
            'status' => $allPresent ? 'ok' : 'error',
            'detail' => implode(' | ', $checks)
        ];
        if (!$allPresent) $success = false;

        // Step 6b: Validate signed XML has signature elements
        $log[] = ['step' => 'Validar Firma Digital', 'status' => 'running'];
        $signedDom = new \DOMDocument();
        $signedDom->loadXML($signedXml);
        $sigChecks = [];
        $sigChecks[] = $signedDom->getElementsByTagName('Signature')->length > 0 ? '✅ Firma' : '❌ Firma falta';
        $sigChecks[] = $signedDom->getElementsByTagName('X509Certificate')->length > 0 ? '✅ Certificado' : '❌ Certificado falta';
        $sigChecks[] = $signedDom->getElementsByTagName('SignatureValue')->length > 0 ? '✅ SignatureValue' : '❌ SignatureValue falta';
        $log[count($log)-1] = ['step' => 'Validar Firma Digital', 'status' => 'ok', 'detail' => implode(' | ', $sigChecks)];

        // Step 7: Filename check
        $rncFromXml = '';
        $encfFromXml = '';
        $rncNode = $dom->getElementsByTagName('RNCEmisor');
        if ($rncNode->length > 0) $rncFromXml = $rncNode->item(0)->textContent;
        $encfNode = $dom->getElementsByTagName('eNCF');
        if ($encfNode->length > 0) $encfFromXml = $encfNode->item(0)->textContent;
        $expectedFilename = "{$rncFromXml}{$encfFromXml}.xml";
        $log[] = ['step' => 'Formato Filename DGII', 'status' => 'ok', 'detail' => $expectedFilename];

        // Step 8: Send (only if explicitly requested)
        if ($sendForReal) {
            $log[] = ['step' => 'ENVIAR a DGII (REAL)', 'status' => 'running'];
            try {
                $result = $api->submitInvoice($signedXml, $token, $env, false);
                $log[count($log)-1] = [
                    'step' => 'ENVIAR a DGII (REAL)',
                    'status' => $result['success'] ? 'ok' : 'error',
                    'detail' => "Status: {$result['status']} | TrackId: " . ($result['track_id'] ?? 'N/A') . ($result['errors'] ? " | Error: {$result['errors']}" : '')
                ];
                if (!$result['success']) $success = false;
            } catch (\Exception $e) {
                $log[count($log)-1] = ['step' => 'ENVIAR a DGII (REAL)', 'status' => 'error', 'detail' => $e->getMessage()];
                $success = false;
            }
        } else {
            $log[] = ['step' => 'Envío a DGII', 'status' => 'skip', 'detail' => 'Modo diagnóstico — no se envió. Usa send=true para enviar de verdad.'];
        }

        return response()->json([
            'success' => $success,
            'log' => $log,
            'xml_preview' => substr($signedXml, 0, 500) . '...',
        ]);
    }

    /**
     * Lightweight DGII connection status check for the topbar pill.
     */
    public function connectionStatus(DgiiAuthService $auth)
    {
        try {
            $settings = Setting::getAll();
            $certPath = $settings['dgii_certificate_path'] ?? '';
            $certPass = $settings['dgii_certificate_password'] ?? '';
            $env = $settings['dgii_env'] ?? 'testing';
            $rnc = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');

            // Check if e-CF is configured at all
            if (empty($certPath) || empty($rnc)) {
                return response()->json([
                    'status' => 'not_configured',
                    'label' => 'e-CF No Configurado',
                    'env' => $env
                ]);
            }

            // Check certificate file exists
            $p12Full = storage_path('app/secure/' . $certPath);
            if (!file_exists($p12Full)) {
                return response()->json([
                    'status' => 'error',
                    'label' => 'Certificado no encontrado',
                    'env' => $env
                ]);
            }

            // Try to get a valid token (uses cache if available)
            $token = $auth->getValidToken($settings);

            return response()->json([
                'status' => 'connected',
                'label' => 'DGII Conectado',
                'env' => $env
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'disconnected',
                'label' => 'DGII Desconectado',
                'error' => $e->getMessage(),
                'env' => $settings['dgii_env'] ?? 'testing'
            ]);
        }
    }

    /**
     * Generate simulation invoices for DGII Step 4 certification.
     * Creates real invoices, processes them through EcfManagerService, and returns results.
     */
    public function generateSimulation(Request $request)
    {
        $request->validate([
            'ecf_type' => 'required|integer|in:31,32,33,34,41,43,44,45,46,47',
            'quantity' => 'required|integer|min:1|max:10',
            'client_id' => 'required|integer|exists:clients,id',
            'is_rfce' => 'sometimes|boolean', // For E32 < 250k
        ]);

        $ecfType = (int) $request->input('ecf_type');
        $qty = (int) $request->input('quantity');
        $clientId = (int) $request->input('client_id');
        $isRfce = $request->boolean('is_rfce', false);

        $client = Client::findOrFail($clientId);
        $svc = app(EcfManagerService::class);
        $results = [];

        // For E33/E34, find enough DIFFERENT accepted E31s to reference
        $referenceEncfs = [];
        if (in_array($ecfType, [33, 34])) {
            $referenceEncfs = Invoice::where('ecf_type', 31)
                ->where('dgii_status', 'accepted')
                ->where('client_id', $clientId)
                ->orderBy('id', 'desc')
                ->take($qty)
                ->pluck('encf')
                ->toArray();
            
            if (count($referenceEncfs) < $qty) {
                return response()->json([
                    'success' => false,
                    'error' => "Se necesitan {$qty} facturas E31 aceptadas para referenciar, pero solo hay " . count($referenceEncfs) . ". Genera primero las E31.",
                ], 422);
            }
        }

        $typeDescriptions = [
            31 => 'Servicio profesional de consultoría',
            32 => $isRfce ? 'Producto de consumo menor' : 'Equipos de manufactura industrial',
            33 => 'Ajuste por cargo adicional',
            34 => 'Descuento por devolución',
            41 => 'Compras de suministros',
            43 => 'Gastos menores operativos',
            44 => 'Servicio régimen especial',
            45 => 'Servicio gubernamental',
            46 => 'Servicio de exportación',
            47 => 'Pago al exterior por servicios',
        ];

        for ($i = 1; $i <= $qty; $i++) {
            $baseAmount = $isRfce ? (3000 * $i) : ($ecfType === 32 ? (260000 * $i) : (5000 * $i));
            
            $inv = new Invoice();
            $inv->invoice_number = 'SIM-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $inv->client_id = $clientId;
            $inv->issue_date = now()->format('Y-m-d');
            $inv->due_date = now()->addDays(30)->format('Y-m-d');
            $inv->currency = 'DOP';
            $inv->is_ecf = true;
            $inv->ecf_type = $ecfType;
            $inv->status = 'sent';
            $inv->subtotal = $baseAmount;
            $inv->tax_rate = 18;
            $inv->tax_amount = round($baseAmount * 0.18, 2);
            $inv->total = round($baseAmount * 1.18, 2);
            $inv->discount_amount = 0;
            $inv->amount_paid = 0;
            $inv->notes = "Simulación Paso 4 - E{$ecfType} #{$i}";

            if (in_array($ecfType, [33, 34]) && !empty($referenceEncfs)) {
                $inv->modified_ncf = $referenceEncfs[$i - 1];
                $inv->modification_code = $ecfType === 33 ? 3 : 1;
            }

            $inv->save();
            $inv->items()->create([
                'description' => ($typeDescriptions[$ecfType] ?? 'Servicio') . " #{$i}",
                'quantity' => $i,
                'unit_price' => $baseAmount / $i,
                'amount' => $baseAmount,
            ]);

            try {
                $r = $svc->processInvoice($inv);
                $inv->refresh();
                $results[] = [
                    'invoice_id' => $inv->id,
                    'encf' => $inv->encf,
                    'dgii_status' => $inv->dgii_status,
                    'track_id' => $inv->dgii_track_id,
                    'total' => $inv->total,
                    'success' => in_array($inv->dgii_status, ['accepted', 'portal_pending']),
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'invoice_id' => $inv->id,
                    'encf' => $inv->encf ?? 'ERROR',
                    'dgii_status' => 'error',
                    'track_id' => null,
                    'total' => $inv->total,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $allSuccess = collect($results)->every(fn($r) => $r['success']);

        return response()->json([
            'success' => $allSuccess,
            'results' => $results,
            'type' => $ecfType,
            'quantity' => $qty,
            'client' => $client->company_name,
        ]);
    }
}

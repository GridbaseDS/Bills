<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Dgii\DgiiAuthService;
use App\Services\Dgii\XmlSignatureService;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Exception;

class RunDgiiTestsCommand extends Command
{
    protected $signature = 'dgii:run-tests';
    protected $description = 'Runs the DGII Set de Pruebas by signing and sending pre-generated XMLs';

    public function handle(DgiiAuthService $authService, XmlSignatureService $signatureService)
    {
        $this->info('Starting DGII Test Runner...');

        $testDir = storage_path('app/dgii_tests');
        if (!File::exists($testDir)) {
            $this->error("Directory $testDir does not exist. Please generate the XMLs first.");
            return 1;
        }

        $files = File::files($testDir);
        
        // Filter and sort XMLs (exclude signed_ files)
        $xmlFiles = array_filter($files, function($f) {
            return $f->getExtension() === 'xml' && !str_starts_with($f->getFilename(), 'signed_');
        });

        usort($xmlFiles, function($a, $b) {
            return strnatcmp($a->getFilename(), $b->getFilename());
        });

        $this->info('Found ' . count($xmlFiles) . ' XML test cases.');

        // Load settings
        $settings = Setting::getAll();
        
        // Authenticate with DGII — always get a fresh token for test runs
        $token = null;
        try {
            $this->info('Authenticating with DGII...');
            // Clear any cached token to ensure a fresh one
            $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
            $env = $settings['dgii_env'] ?? 'testing';
            \Illuminate\Support\Facades\Cache::forget("dgii_bearer_token_{$rncEmisor}_{$env}");
            
            $token = $authService->getValidToken($settings);
            $this->info('Token obtained successfully.');
        } catch (Exception $e) {
            $this->error('Failed to authenticate: ' . $e->getMessage());
            return 1;
        }

        // Get certificate path and password from settings
        $certPath = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
        $password = $settings['dgii_certificate_password'] ?? '';
        
        if (!File::exists($certPath) || empty($password)) {
            $this->error('Certificate or password not configured. Check Settings > DGII.');
            return 1;
        }

        // Determine base URLs based on environment
        // DGII uses different domains for e-CF vs RFCE
        $env = $settings['dgii_env'] ?? 'testing';
        $ecfBaseUrl = $env === 'production'
            ? 'https://ecf.dgii.gov.do/ecf'
            : 'https://ecf.dgii.gov.do/certecf';
        $fcBaseUrl = $env === 'production'
            ? 'https://fc.dgii.gov.do/ecf'
            : 'https://fc.dgii.gov.do/certecf';

        $successCount = 0;
        $errorCount = 0;

        foreach ($xmlFiles as $file) {
            $filename = $file->getFilename();
            $this->info("Processing $filename ...");

            $unsignedXml = File::get($file->getPathname());
            
            // For RFCE, we need to handle CodigoSeguridadeCF
            // This code comes from the first 6 chars of the MD5 hash of the SignatureValue
            $isRfce = str_starts_with($filename, 'rfce');
            
            // Sign the XML
            try {
                if ($isRfce) {
                    // RFCE requires CodigoSeguridadeCF which is derived from the signature
                    // Step 1: Add placeholder CodigoSeguridadeCF if not present
                    if (strpos($unsignedXml, '<CodigoSeguridadeCF>') === false) {
                        $unsignedXml = str_replace('</Encabezado>', '<CodigoSeguridadeCF>000000</CodigoSeguridadeCF></Encabezado>', $unsignedXml);
                    }
                    
                    // Step 2: Sign with placeholder to get the security code
                    $tempSigned = $signatureService->signXml($unsignedXml, $certPath, $password);
                    $securityCode = $signatureService->getSecurityCode($tempSigned);
                    
                    // Step 3: Replace placeholder with real code and re-sign
                    $unsignedXml = preg_replace(
                        '/<CodigoSeguridadeCF>[^<]*<\/CodigoSeguridadeCF>/',
                        '<CodigoSeguridadeCF>' . $securityCode . '</CodigoSeguridadeCF>',
                        $unsignedXml
                    );
                    $signedXml = $signatureService->signXml($unsignedXml, $certPath, $password);
                } else {
                    $signedXml = $signatureService->signXml($unsignedXml, $certPath, $password);
                }
            } catch (Exception $e) {
                $this->error("Failed to sign $filename: " . $e->getMessage());
                $errorCount++;
                continue;
            }

            // Send to DGII
            try {
                // Skip Type 32 < 250k from e-CF endpoint (must be uploaded via portal's FC<250k section)
                if (!$isRfce) {
                    preg_match('/<TipoeCF>(\d+)<\/TipoeCF>/', $signedXml, $tipoMatch);
                    preg_match('/<MontoTotal>([\d.]+)<\/MontoTotal>/', $signedXml, $montoMatch);
                    $tipo = $tipoMatch[1] ?? '';
                    $monto = floatval($montoMatch[1] ?? 0);
                    
                    if ($tipo === '32' && $monto < 250000) {
                        $this->warn("SKIP $filename (Type 32, Monto $monto < 250k) -> Upload via portal FC<250k section");
                        continue;
                    }
                }

                $endpoint = $isRfce 
                    ? "$fcBaseUrl/recepcionfc/api/recepcion/ecf" 
                    : "$ecfBaseUrl/recepcion/api/facturaselectronicas";

                $this->info("Sending $filename to $endpoint ...");

                // DGII requires specific filename format: {RNCEmisor}{eNCF}.xml for ALL uploads
                $sendFilename = $filename;
                preg_match('/<RNCEmisor>(\d+)<\/RNCEmisor>/', $signedXml, $rncMatch);
                preg_match('/<eNCF>([^<]+)<\/eNCF>/', $signedXml, $encfMatch);
                if (!empty($rncMatch[1]) && !empty($encfMatch[1])) {
                    $sendFilename = $rncMatch[1] . $encfMatch[1] . '.xml';
                }

                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ])
                    ->attach('xml', $signedXml, $sendFilename, ['Content-Type' => 'text/xml'])
                    ->post($endpoint);

                $responseBody = $response->json();

                // ECF returns trackId, RFCE returns codigo/estado
                if ($response->successful() && isset($responseBody['trackId'])) {
                    $this->info("SUCCESS $filename -> TrackId: " . $responseBody['trackId']);
                    $successCount++;
                } elseif (isset($responseBody['codigo']) && $responseBody['codigo'] == 1) {
                    $this->info("SUCCESS $filename -> Estado: " . ($responseBody['estado'] ?? 'Aceptado') . ", eNCF: " . ($responseBody['encf'] ?? ''));
                    $successCount++;
                } else {
                    $this->warn("Response for $filename (HTTP {$response->status()}): " . $response->body());
                    $errorCount++;
                }

            } catch (Exception $e) {
                $this->error("Failed to send $filename: " . $e->getMessage());
                $errorCount++;
            }
            
            // Pause between requests
            usleep(500000); // 0.5s
        }

        $this->newLine();
        $this->info("Done! Results: $successCount SUCCESS, $errorCount ERRORS out of " . count($xmlFiles) . " total.");
        
        return $errorCount > 0 ? 1 : 0;
    }
}

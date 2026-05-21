<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Dgii\DgiiAuthService;
use App\Services\Dgii\XmlSignatureService;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
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
            $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
            $env = $settings['dgii_env'] ?? 'testing';
            Cache::forget("dgii_bearer_token_{$rncEmisor}_{$env}");
            
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

        // Determine base URLs
        $ecfBaseUrl = $env === 'production'
            ? 'https://ecf.dgii.gov.do/ecf'
            : 'https://ecf.dgii.gov.do/certecf';
        $fcBaseUrl = $env === 'production'
            ? 'https://fc.dgii.gov.do/ecf'
            : 'https://fc.dgii.gov.do/certecf';

        $successCount = 0;
        $errorCount = 0;

        // === PHASE 0: Pre-sign FC<250k e-CFs to get their CodigoSeguridadeCF ===
        // The RFCE's CodigoSeguridadeCF MUST match the FULL e-CF's signature hash, NOT its own.
        $securityCodeMap = []; // eNCF => security code
        $uploadDir = storage_path('app/dgii_tests/fc_250k_upload');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        foreach ($xmlFiles as $file) {
            $filename = $file->getFilename();
            if (str_starts_with($filename, 'rfce')) continue;
            
            $xml = File::get($file->getPathname());
            preg_match('/<TipoeCF>(\d+)<\/TipoeCF>/', $xml, $tipoMatch);
            preg_match('/<MontoTotal>([\d.]+)<\/MontoTotal>/', $xml, $montoMatch);
            preg_match('/<eNCF>([^<]+)<\/eNCF>/', $xml, $encfMatch);
            preg_match('/<RNCEmisor>(\d+)<\/RNCEmisor>/', $xml, $rncMatch);
            
            $tipo = $tipoMatch[1] ?? '';
            $monto = floatval($montoMatch[1] ?? 0);
            $encf = $encfMatch[1] ?? '';
            $rnc = $rncMatch[1] ?? '';
            
            if ($tipo === '32' && $monto < 250000 && $encf) {
                $this->info("Pre-signing FC<250k: $filename (eNCF: $encf, Monto: $monto)");
                try {
                    $signedFc = $signatureService->signXml($xml, $certPath, $password);
                    $secCode = $signatureService->getSecurityCode($signedFc);
                    $securityCodeMap[$encf] = $secCode;
                    
                    // Save signed file for portal upload
                    $uploadName = $rnc . $encf . '.xml';
                    File::put("$uploadDir/$uploadName", $signedFc);
                    $this->info("  -> Security code: $secCode, saved: $uploadName");
                } catch (Exception $e) {
                    $this->error("  -> Failed: " . $e->getMessage());
                }
            }
        }

        // === PHASE 1: Categorize files ===
        $baseFiles = [];
        $noteFiles = [];
        foreach ($xmlFiles as $file) {
            $xml = File::get($file->getPathname());
            preg_match('/<TipoeCF>(\d+)<\/TipoeCF>/', $xml, $tipoMatch);
            $tipo = $tipoMatch[1] ?? '';
            if (in_array($tipo, ['33', '34'])) {
                $noteFiles[] = $file;
            } else {
                $baseFiles[] = $file;
            }
        }

        $orderedFiles = array_merge($baseFiles, $noteFiles);
        $baseCount = count($baseFiles);
        $currentIndex = 0;

        // === PHASE 2: Send files ===
        foreach ($orderedFiles as $file) {
            $currentIndex++;
            
            if ($currentIndex === $baseCount + 1 && !empty($noteFiles)) {
                $this->info('');
                $this->info('=== Waiting 30s for base invoices to be processed before sending Notes... ===');
                sleep(30);
            }
            
            $filename = $file->getFilename();
            $this->info("Processing $filename ...");

            $unsignedXml = File::get($file->getPathname());
            $isRfce = str_starts_with($filename, 'rfce');
            
            // Sign the XML
            try {
                if ($isRfce) {
                    // Get matching security code from the FULL e-CF signature
                    preg_match('/<eNCF>([^<]+)<\/eNCF>/', $unsignedXml, $encfMatch);
                    $rfceEncf = $encfMatch[1] ?? '';
                    $securityCode = $securityCodeMap[$rfceEncf] ?? '000000';
                    $this->info("  CodigoSeguridadeCF from full e-CF: $securityCode (eNCF: $rfceEncf)");
                    
                    // Insert/replace CodigoSeguridadeCF
                    if (strpos($unsignedXml, '<CodigoSeguridadeCF>') === false) {
                        $unsignedXml = str_replace('</Encabezado>', '<CodigoSeguridadeCF>' . $securityCode . '</CodigoSeguridadeCF></Encabezado>', $unsignedXml);
                    } else {
                        $unsignedXml = preg_replace(
                            '/<CodigoSeguridadeCF>[^<]*<\/CodigoSeguridadeCF>/',
                            '<CodigoSeguridadeCF>' . $securityCode . '</CodigoSeguridadeCF>',
                            $unsignedXml
                        );
                    }
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
                // Skip Type 32 < 250k (already pre-signed and saved for portal upload)
                if (!$isRfce) {
                    preg_match('/<TipoeCF>(\d+)<\/TipoeCF>/', $signedXml, $tipoMatch);
                    preg_match('/<MontoTotal>([\d.]+)<\/MontoTotal>/', $signedXml, $montoMatch);
                    $tipo = $tipoMatch[1] ?? '';
                    $monto = floatval($montoMatch[1] ?? 0);
                    
                    if ($tipo === '32' && $monto < 250000) {
                        $this->warn("SKIP $filename (Type 32, Monto $monto < 250k) -> Already saved for portal upload");
                        continue;
                    }
                }

                $endpoint = $isRfce 
                    ? "$fcBaseUrl/recepcionfc/api/recepcion/ecf" 
                    : "$ecfBaseUrl/recepcion/api/facturaselectronicas";

                $this->info("Sending $filename to $endpoint ...");

                // DGII requires filename format: {RNCEmisor}{eNCF}.xml
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
            
            usleep(500000); // 0.5s pause
        }

        $this->newLine();
        $this->info("Done! Results: $successCount SUCCESS, $errorCount ERRORS out of " . count($xmlFiles) . " total.");
        
        if (!empty($securityCodeMap)) {
            $this->newLine();
            $this->info("=== FC<250k signed files saved to: $uploadDir ===");
            $this->info("Upload these files to the DGII portal under 'Facturas de consumo < 250Mil'");
        }
        
        return $errorCount > 0 ? 1 : 0;
    }
}

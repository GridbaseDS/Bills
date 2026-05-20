<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Dgii\EcfManagerService;
use App\Services\Dgii\DgiiAuthService;
use App\Services\Dgii\XmlSignatureService;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Exception;

class RunDgiiTestsCommand extends Command
{
    protected $signature = 'dgii:run-tests';
    protected $description = 'Runs the DGII Set de Pruebas by signing and sending pre-generated XMLs';

    public function handle(EcfManagerService $ecfManager, DgiiAuthService $authService, XmlSignatureService $signatureService)
    {
        $this->info('Starting DGII Test Runner...');

        $testDir = storage_path('app/dgii_tests');
        if (!File::exists($testDir)) {
            $this->error("Directory $testDir does not exist. Please generate the XMLs first.");
            return;
        }

        $files = File::files($testDir);
        
        // Filter and sort XMLs
        $xmlFiles = array_filter($files, function($f) {
            return $f->getExtension() === 'xml';
        });

        usort($xmlFiles, function($a, $b) {
            return strnatcmp($a->getFilename(), $b->getFilename());
        });

        $this->info('Found ' . count($xmlFiles) . ' XML test cases.');

        $token = null;
        try {
            $this->info('Authenticating with DGII...');
            $token = $authService->getValidToken(Setting::getAll());
            $this->info('Token obtained successfully.');
        } catch (Exception $e) {
            $this->error('Failed to authenticate: ' . $e->getMessage());
            return;
        }

        $certPath = storage_path('app/secure/certificado.p12');
        $password = \App\Models\Setting::getSetting('dgii_cert_password');
        
        if (!File::exists($certPath) || empty($password)) {
            $this->error('Certificate or password not configured.');
            return;
        }

        foreach ($xmlFiles as $file) {
            $filename = $file->getFilename();
            $this->info("Processing $filename ...");

            $unsignedXml = File::get($file->getPathname());
            
            // Sign the XML
            try {
                $signedXml = $signatureService->signXml($unsignedXml, $certPath, $password);
            } catch (Exception $e) {
                $this->error("Failed to sign $filename: " . $e->getMessage());
                continue;
            }

            // Send to DGII
            try {
                $isRfce = strpos($filename, 'rfce') !== false;
                
                $this->info("Sending $filename to DGII...");
                
                // We need to use Guzzle directly because EcfManagerService expects an Invoice model
                // But for tests we just have raw XML.
                
                $client = new \GuzzleHttp\Client();
                $baseUrl = 'https://ecf.dgii.gov.do/CerteCF'; // Test environment
                
                if ($isRfce) {
                    $endpoint = $baseUrl . '/RecepcionFC';
                } else {
                    $endpoint = $baseUrl . '/Recepcion';
                }

                $response = $client->post($endpoint, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'text/xml',
                        'Accept' => 'application/json'
                    ],
                    'body' => $signedXml
                ]);

                $responseBody = json_decode($response->getBody()->getContents(), true);
                
                if (isset($responseBody['trackId'])) {
                    $this->info("Success! $filename TrackId: " . $responseBody['trackId']);
                } else {
                    $this->warn("Response for $filename: " . json_encode($responseBody));
                }

            } catch (Exception $e) {
                $this->error("Failed to send $filename: " . $e->getMessage());
            }
            
            sleep(2); // Pause between requests to not overwhelm DGII test server
        }

        $this->info('DGII Test Runner finished.');
    }
}

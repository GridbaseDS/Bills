<?php
/**
 * Generates signed XML files for the 4 FC<250k cases
 * that need to be manually uploaded to the DGII certification portal.
 */
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Dgii\XmlSignatureService;
use App\Models\Setting;

$signer = new XmlSignatureService();
$settings = Setting::getAll();
$certPath = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
$password = $settings['dgii_certificate_password'] ?? '';

echo "Certificate: $certPath\n";
echo "Exists: " . (file_exists($certPath) ? 'YES' : 'NO') . "\n\n";

$testDir = storage_path('app/dgii_tests');
$outputDir = storage_path('app/dgii_tests/fc_250k_upload');

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// The 4 FC<250k eNCFs (same as RFCE cases)
$fc250kEncfs = ['E320000000012', 'E320000000013', 'E320000000014', 'E320000000015'];

// Find matching ecf files
$files = glob($testDir . '/ecf_*.xml');
sort($files);

foreach ($files as $file) {
    $xml = file_get_contents($file);
    
    preg_match('/<eNCF>([^<]+)<\/eNCF>/', $xml, $encfMatch);
    $encf = $encfMatch[1] ?? '';
    
    if (!in_array($encf, $fc250kEncfs)) {
        continue;
    }
    
    preg_match('/<RNCEmisor>(\d+)<\/RNCEmisor>/', $xml, $rncMatch);
    $rnc = $rncMatch[1] ?? '';
    
    $filename = basename($file);
    echo "Signing $filename (eNCF: $encf)...\n";
    
    $signedXml = $signer->signXml($xml, $certPath, $password);
    
    // Save with correct filename format: {RNCEmisor}{eNCF}.xml
    $outputFilename = $rnc . $encf . '.xml';
    $outputPath = $outputDir . '/' . $outputFilename;
    file_put_contents($outputPath, $signedXml);
    
    echo "  -> Saved: $outputFilename (" . strlen($signedXml) . " bytes)\n";
}

echo "\n=== Signed FC<250k files saved to: $outputDir ===\n";
echo "Upload these 4 files to the DGII portal under 'Facturas de consumo < 250Mil'\n";

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$settings = App\Models\Setting::getAll();
$rncComprador = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
$certPath = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
$certPass = $settings['dgii_certificate_password'] ?? '';
$env = $settings['dgii_env'] ?? 'testing';

echo "RNC Comprador: {$rncComprador}\n";
echo "Environment: {$env}\n";
echo "Cert exists: " . (file_exists($certPath) ? 'YES' : 'NO') . "\n\n";

$acecfXml = '<?xml version="1.0" encoding="utf-8"?>
<ACECF xmlns="urn:dgii.gov.do:ACECF" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <DetalleAprobacionComercial>
    <Version>1.0</Version>
    <RNCEmisor>131880681</RNCEmisor>
    <eNCF>E310000000004</eNCF>
    <FechaEmision>01-04-2020</FechaEmision>
    <MontoTotal>18283.00</MontoTotal>
    <RNCComprador>' . $rncComprador . '</RNCComprador>
    <Estado>1</Estado>
    <FechaHoraAprobacionComercial>23-05-2026 01:29:09</FechaHoraAprobacionComercial>
  </DetalleAprobacionComercial>
</ACECF>';

echo "=== UNSIGNED XML ===\n";
echo $acecfXml . "\n\n";

// Sign
$signer = app(App\Services\Dgii\XmlSignatureService::class);
$signed = $signer->signXml($acecfXml, $certPath, $certPass);
echo "=== SIGNED XML (first 800 chars) ===\n";
echo substr($signed, 0, 800) . "\n\n";

// Try sending
$authService = app(App\Services\Dgii\DgiiAuthService::class);
$token = $authService->getValidToken($settings);
echo "Token: " . substr($token, 0, 30) . "...\n\n";

$baseUrl = $env === 'production' ? 'https://ecf.dgii.gov.do/ecf' : 'https://ecf.dgii.gov.do/CerteCF';
$endpoint = "{$baseUrl}/AprobacionComercial/api/AprobacionComercial";

$sendFilename = "{$rncComprador}E310000000004.xml";

echo "Endpoint: {$endpoint}\n";
echo "Filename: {$sendFilename}\n\n";

// Use curl for maximum control
$ch = curl_init();
$tmpFile = tempnam(sys_get_temp_dir(), 'acecf_') . '.xml';
file_put_contents($tmpFile, $signed);

$cfile = new CURLFile($tmpFile, 'text/xml', $sendFilename);

curl_setopt_array($ch, [
    CURLOPT_URL => $endpoint,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['xml' => $cfile],
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => true,
]);

$stderr = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $stderr);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

rewind($stderr);
$verboseLog = stream_get_contents($stderr);
fclose($stderr);

curl_close($ch);
@unlink($tmpFile);

echo "=== CURL VERBOSE ===\n{$verboseLog}\n";
echo "=== RESPONSE (HTTP {$httpCode}) ===\n{$response}\n";
echo "Content-Type: {$contentType}\n";

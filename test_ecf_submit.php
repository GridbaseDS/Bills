<?php
// End-to-end test: sign XML + submit to DGII
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Dgii\XmlSignatureService;
use App\Services\Dgii\DgiiAuthService;

$p12Path = storage_path('app/secure/certificado_moderno.p12');
$password = 'SamDP9903';
$signer = new XmlSignatureService();

// 1. Get token
echo "=== Getting Token ===\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://ecf.dgii.gov.do/certecf/Autenticacion/api/Autenticacion/Semilla',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$semillaXml = curl_exec($ch);
curl_close($ch);

$signedSemilla = $signer->signXml($semillaXml, $p12Path, $password);

$ch2 = curl_init();
$boundary = '----FormBoundary' . bin2hex(random_bytes(8));
$body = "--$boundary\r\nContent-Disposition: form-data; name=\"xml\"; filename=\"semilla.xml\"\r\nContent-Type: text/xml\r\n\r\n$signedSemilla\r\n--$boundary--\r\n";
curl_setopt_array($ch2, [
    CURLOPT_URL => 'https://ecf.dgii.gov.do/certecf/Autenticacion/api/Autenticacion/ValidarSemilla',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => ["Content-Type: multipart/form-data; boundary=$boundary", "Accept: application/json"],
]);
$tokenResp = curl_exec($ch2);
curl_close($ch2);
$tokenData = json_decode($tokenResp, true);
$token = $tokenData['token'] ?? null;

if (!$token) {
    echo "FAILED to get token: $tokenResp\n";
    exit(1);
}
echo "Token: " . substr($token, 0, 40) . "...\n\n";

// 2. Sign an ECF XML
echo "=== Signing ecf_05.xml (Type 31) ===\n";
$xmlPath = storage_path('app/dgii_tests/ecf_05.xml');
$unsignedXml = file_get_contents($xmlPath);
echo "Unsigned XML length: " . strlen($unsignedXml) . "\n";
echo "First 200 chars: " . substr($unsignedXml, 0, 200) . "\n\n";

$signedXml = $signer->signXml($unsignedXml, $p12Path, $password);
echo "Signed XML length: " . strlen($signedXml) . "\n";
echo "Has Signature: " . (strpos($signedXml, '<Signature') !== false ? 'YES' : 'NO') . "\n";
echo "Has xmlns: " . (strpos($signedXml, 'xmlns=') !== false ? 'YES' : 'NO') . "\n\n";

// Show the signed XML structure (first 500 chars)
echo "=== Signed XML (first 500 chars) ===\n";
echo substr($signedXml, 0, 500) . "\n...\n\n";

// 3. Submit to DGII
echo "=== Submitting to DGII ===\n";
$endpoint = 'https://ecf.dgii.gov.do/certecf/recepcion/api/facturaselectronicas';

$ch3 = curl_init();
$boundary3 = '----FormBoundary' . bin2hex(random_bytes(8));
$body3 = "--$boundary3\r\nContent-Disposition: form-data; name=\"xml\"; filename=\"ecf_05.xml\"\r\nContent-Type: text/xml\r\n\r\n$signedXml\r\n--$boundary3--\r\n";

curl_setopt_array($ch3, [
    CURLOPT_URL => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body3,
    CURLOPT_HTTPHEADER => [
        "Content-Type: multipart/form-data; boundary=$boundary3",
        "Authorization: Bearer $token",
        "Accept: application/json",
    ],
]);
$response = curl_exec($ch3);
$httpCode = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

echo "HTTP $httpCode\n";
echo "Response: $response\n";

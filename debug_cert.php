<?php
// Final test using the new XmlSignatureService

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Dgii\XmlSignatureService;

$p12Path = storage_path('app/secure/certificado_moderno.p12');
$password = 'SamDP9903';

$signer = new XmlSignatureService();

// 1. Fetch real semilla from DGII
echo "=== FETCHING SEMILLA ===\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://ecf.dgii.gov.do/certecf/Autenticacion/api/Autenticacion/Semilla',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10,
]);
$semillaXml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP $httpCode\n";
echo "Semilla XML:\n$semillaXml\n\n";

// 2. Sign with our service
echo "=== SIGNING ===\n";
$signedXml = $signer->signXml($semillaXml, $p12Path, $password);
echo "Signed XML length: " . strlen($signedXml) . "\n";
echo "Signed XML:\n$signedXml\n\n";

// 3. Submit to DGII
echo "=== SUBMITTING TO DGII ===\n";
$ch2 = curl_init();
$boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(8));
$postBody = "--$boundary\r\nContent-Disposition: form-data; name=\"xml\"; filename=\"semilla.xml\"\r\nContent-Type: text/xml\r\n\r\n$signedXml\r\n--$boundary--\r\n";

curl_setopt_array($ch2, [
    CURLOPT_URL => 'https://ecf.dgii.gov.do/certecf/Autenticacion/api/Autenticacion/ValidarSemilla',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postBody,
    CURLOPT_HTTPHEADER => [
        "Content-Type: multipart/form-data; boundary=$boundary",
        "Accept: application/json",
    ],
]);
$response = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Code: $httpCode2\n";
echo "Response: $response\n";

if ($httpCode2 === 200) {
    echo "\n=== SUCCESS! TOKEN OBTAINED ===\n";
    $data = json_decode($response, true);
    if ($data) {
        echo "Token: " . substr($data['token'] ?? 'N/A', 0, 50) . "...\n";
        echo "Expira: " . ($data['expira'] ?? 'N/A') . "\n";
    }
}

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Dgii\XmlSignatureService;
use App\Models\Setting;

$signatureService = app(XmlSignatureService::class);
$p12Path = storage_path('app/secure/certificado_moderno.p12');
$p12Password = 'SamDP9903';

$baseUrl = 'https://ecf.dgii.gov.do/certecf';

// 1. Get Semilla
$ch = curl_init("{$baseUrl}/Autenticacion/api/Autenticacion/Semilla");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$semillaXml = curl_exec($ch);
curl_close($ch);

// 2. Sign Semilla
$signedSemillaXml = $signatureService->signXml($semillaXml, $p12Path, $p12Password);
file_put_contents('storage/app/dgii_tests/signed_semilla.xml', $signedSemillaXml);

// 3. Validate Semilla (Try raw XML body first, if fails try multipart)
$ch = curl_init("{$baseUrl}/Autenticacion/api/Autenticacion/ValidarSemilla");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POST, true);
// Try multipart/form-data just in case DGII expects it for Semilla too
$cFile = new CURLFile('storage/app/dgii_tests/signed_semilla.xml', 'application/xml', 'signed_semilla.xml');
curl_setopt($ch, CURLOPT_POSTFIELDS, ['xml' => $cFile]);
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     'Content-Type: application/xml',
//     'Accept: application/json'
// ]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpcode\n";
echo "Response Body: $response\n";

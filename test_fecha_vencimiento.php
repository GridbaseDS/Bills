<?php
/**
 * Direct DGII e-CF submission test - iterates different dates quickly
 * Run: php test_fecha_vencimiento.php [date]
 * Example: php test_fecha_vencimiento.php 31-12-2026
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

// Get the date to test from command line or default
$testDate = $argv[1] ?? '31-12-2026';
echo "Testing FechaVencimientoSecuencia: $testDate\n";

// Get current sequence and increment
$seqKey = 'dgii_next_e_ncf_31';
$seq = (int) Setting::where('setting_key', $seqKey)->value('setting_value');
$eNCF = 'E31' . str_pad($seq, 10, '0', STR_PAD_LEFT);
echo "Using eNCF: $eNCF (seq $seq)\n";

// Build minimal valid XML
$settings = [
    'company_tax_id' => Setting::where('setting_key', 'company_tax_id')->value('setting_value'),
    'company_name' => Setting::where('setting_key', 'company_name')->value('setting_value'),
    'company_address' => Setting::where('setting_key', 'company_address')->value('setting_value') ?? 'Santo Domingo',
    'company_email' => Setting::where('setting_key', 'company_email')->value('setting_value') ?? 'bills@gridbase.com.do',
];

$rncEmisor = $settings['company_tax_id'];
$fechaEmision = date('d-m-Y');
$fechaHoraFirma = date('d-m-Y H:i:s');

$xml = '<?xml version="1.0" encoding="utf-8"?>';
$xml .= '<ECF>';
$xml .= '<Encabezado>';
$xml .= '<Version>1.0</Version>';
$xml .= '<IdDoc>';
$xml .= '<TipoeCF>31</TipoeCF>';
$xml .= "<eNCF>$eNCF</eNCF>";
$xml .= "<FechaVencimientoSecuencia>$testDate</FechaVencimientoSecuencia>";
$xml .= '<TipoIngresos>01</TipoIngresos>';
$xml .= '<TipoPago>1</TipoPago>';
$xml .= '</IdDoc>';
$xml .= '<Emisor>';
$xml .= "<RNCEmisor>$rncEmisor</RNCEmisor>";
$xml .= '<RazonSocialEmisor>' . htmlspecialchars($settings['company_name'], ENT_XML1) . '</RazonSocialEmisor>';
$xml .= '<DireccionEmisor>' . htmlspecialchars(substr($settings['company_address'], 0, 100), ENT_XML1) . '</DireccionEmisor>';
$xml .= '<CorreoEmisor>' . htmlspecialchars($settings['company_email'], ENT_XML1) . '</CorreoEmisor>';
$xml .= "<FechaEmision>$fechaEmision</FechaEmision>";
$xml .= '</Emisor>';
$xml .= '<Comprador>';
$xml .= "<RNCComprador>$rncEmisor</RNCComprador>";
$xml .= '<RazonSocialComprador>Test Empresa</RazonSocialComprador>';
$xml .= '</Comprador>';
$xml .= '<Totales>';
$xml .= '<MontoGravadoTotal>1000.00</MontoGravadoTotal>';
$xml .= '<MontoGravadoI1>1000.00</MontoGravadoI1>';
$xml .= '<ITBIS1>18</ITBIS1>';
$xml .= '<TotalITBIS>180.00</TotalITBIS>';
$xml .= '<TotalITBIS1>180.00</TotalITBIS1>';
$xml .= '<MontoTotal>1180.00</MontoTotal>';
$xml .= '</Totales>';
$xml .= '</Encabezado>';
$xml .= '<DetallesItems>';
$xml .= '<Item>';
$xml .= '<NumeroLinea>1</NumeroLinea>';
$xml .= '<IndicadorFacturacion>1</IndicadorFacturacion>';
$xml .= '<NombreItem>Servicio de prueba</NombreItem>';
$xml .= '<IndicadorBienoServicio>2</IndicadorBienoServicio>';
$xml .= '<CantidadItem>1.00</CantidadItem>';
$xml .= '<UnidadMedida>43</UnidadMedida>';
$xml .= '<PrecioUnitarioItem>1000.0000</PrecioUnitarioItem>';
$xml .= '<MontoItem>1000.00</MontoItem>';
$xml .= '</Item>';
$xml .= '</DetallesItems>';
$xml .= "<FechaHoraFirma>$fechaHoraFirma</FechaHoraFirma>";
$xml .= '</ECF>';

echo "XML (before signing):\n$xml\n\n";

// Sign the XML
$signatureService = app(\App\Services\Dgii\XmlSignatureService::class);
$p12Path = storage_path('app/secure/' . Setting::where('setting_key', 'dgii_certificate_path')->value('setting_value'));
$p12Pass = Setting::where('setting_key', 'dgii_certificate_password')->value('setting_value');
echo "Certificate: $p12Path\n";
$signedXml = $signatureService->signXml($xml, $p12Path, $p12Pass);

if (!$signedXml) {
    echo "ERROR: Failed to sign XML\n";
    exit(1);
}

echo "Signed XML length: " . strlen($signedXml) . " bytes\n";

// Submit to DGII
$dgiiUrl = 'https://ecf.dgii.gov.do/CerteCF/api/ServicioRecepcionECF';

// Get auth token using settings array like production code
$allSettings = Setting::pluck('setting_value', 'setting_key')->toArray();
$authService = app(\App\Services\Dgii\DgiiAuthService::class);
try {
    $token = $authService->getValidToken($allSettings);
} catch (\Exception $e) {
    echo "ERROR: Failed to get auth token: " . $e->getMessage() . "\n";
    exit(1);
}
echo "Auth token obtained: " . substr($token, 0, 20) . "...\n";

// Send as multipart/form-data with 'xml' field (matching DgiiApiService)
$dgiiUrl = 'https://ecf.dgii.gov.do/certecf/recepcion/api/facturaselectronicas';
$sendFilename = "{$rncEmisor}{$eNCF}.xml";
echo "Sending to: $dgiiUrl as $sendFilename\n";

$tmpFile = tempnam(sys_get_temp_dir(), 'ecf');
file_put_contents($tmpFile, $signedXml);

$ch = curl_init($dgiiUrl);
$cfile = new CURLFile($tmpFile, 'application/xml', $sendFilename);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['xml' => $cfile],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
unlink($tmpFile);

echo "\nHTTP $httpCode\n";
echo "Response: $response\n";

// Only increment sequence if successful
if ($httpCode === 200 || $httpCode === 202) {
    Setting::where('setting_key', $seqKey)->update(['setting_value' => $seq + 1]);
    echo "\nSEQUENCE INCREMENTED to " . ($seq + 1) . "\n";
} else {
    // Still increment to avoid reuse
    Setting::where('setting_key', $seqKey)->update(['setting_value' => $seq + 1]);
    echo "\nSequence burned (incremented to " . ($seq + 1) . " to avoid reuse)\n";
}

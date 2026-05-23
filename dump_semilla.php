<?php
require 'vendor/autoload.php';
use App\Services\Dgii\XmlSignatureService;

$service = new XmlSignatureService();
$xml = '<?xml version="1.0" encoding="utf-8"?>
<SemillaModel xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <valor>xfUzY/U0VzR5I7VROQcQn5J9O36O5f65p8W0dF9i6d4=</valor>
  <fecha>2026-05-20T12:37:36.0925528-04:00</fecha>
</SemillaModel>';

try {
    $signed = $service->signXml($xml, 'storage/app/secure/certificado_moderno.p12', 'SamDP9903');
    file_put_contents('signed_semilla_test.xml', $signed);
    echo "Done";
} catch (Exception $e) {
    echo $e->getMessage();
}

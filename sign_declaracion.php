<?php
$xmlFile = __DIR__ . '/storage/app/declaracion_jurada.xml';
$p12File = __DIR__ . '/storage/app/secure/certificado.p12';
$password = 'SamDP9903';
$outputFile = __DIR__ . '/storage/app/declaracion_jurada_firmada.xml';

$xmlContent = file_get_contents($xmlFile);
$p12Content = file_get_contents($p12File);

if (!openssl_pkcs12_read($p12Content, $certs, $password)) {
    die("Error reading P12: " . openssl_error_string() . "\n");
}

echo "Certificate loaded OK\n";

$doc = new DOMDocument();
$doc->loadXML($xmlContent);
$doc->preserveWhiteSpace = false;
$doc->formatOutput = false;

// Canonicalize
$canonicalXml = $doc->C14N();

// Sign
$privateKey = openssl_pkey_get_private($certs['pkey']);
$signature = '';
openssl_sign($canonicalXml, $signature, $privateKey, OPENSSL_ALGO_SHA256);
$signatureB64 = base64_encode($signature);

// Digest
$digestValue = base64_encode(hash('sha256', $canonicalXml, true));

// Certificate
$certClean = str_replace(
    ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"],
    '',
    $certs['cert']
);

$signatureXml = '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">'
    . '<SignedInfo>'
    . '<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" />'
    . '<SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256" />'
    . '<Reference URI="">'
    . '<Transforms><Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" /></Transforms>'
    . '<DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256" />'
    . '<DigestValue>' . $digestValue . '</DigestValue>'
    . '</Reference>'
    . '</SignedInfo>'
    . '<SignatureValue>' . $signatureB64 . '</SignatureValue>'
    . '<KeyInfo><X509Data><X509Certificate>' . $certClean . '</X509Certificate></X509Data></KeyInfo>'
    . '</Signature>';

// Insert before closing tag
$signedXml = str_replace('</DeclaracionJurada>', $signatureXml . '</DeclaracionJurada>', $xmlContent);

file_put_contents($outputFile, $signedXml);
echo "Signed XML saved to: $outputFile\n";
echo "Size: " . strlen($signedXml) . " bytes\n";

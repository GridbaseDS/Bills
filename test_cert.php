<?php
$p12Content = file_get_contents('storage/app/secure/certificado_moderno.p12');
if (openssl_pkcs12_read($p12Content, $certs, 'SamDP9903')) {
    $certData = openssl_x509_parse($certs['cert']);
    echo "Subject: \n";
    print_r($certData['subject']);
    echo "\nExtensions: \n";
    print_r($certData['extensions'] ?? 'No extensions');
} else {
    echo "Failed to read p12. Password or file might be wrong.\n";
}

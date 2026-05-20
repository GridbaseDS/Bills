<?php

namespace App\Services\Dgii;

use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Log;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class XmlSignatureService
{
    /**
     * Signs an XML document using XMLDSig Enveloped signature and a PKCS#12 (.p12/.pfx) certificate.
     *
     * @param string $xmlContent Unsigned XML content.
     * @param string $p12Path Absolute path to the certificate file.
     * @param string $password Password for the certificate.
     * @return string Signed XML content.
     * @throws Exception
     */
    public function signXml(string $xmlContent, string $p12Path, string $password): string
    {
        if (!file_exists($p12Path)) {
            throw new Exception("El archivo del certificado digital no existe en la ruta especificada: {$p12Path}");
        }

        // 1. Extract private key and certificate chain from P12
        $p12Content = file_get_contents($p12Path);
        $certs = [];
        
        if (!openssl_pkcs12_read($p12Content, $certs, $password)) {
            throw new Exception("Error al leer el certificado digital (.p12). Verifique que la contraseña sea correcta.");
        }

        $privateKey = $certs['pkey'] ?? null;
        $publicKey = $certs['cert'] ?? null;

        if (!$privateKey || !$publicKey) {
            throw new Exception("No se pudo extraer la clave privada o el certificado del archivo .p12.");
        }

        // 2. Load XML document
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        
        if (!$doc->loadXML($xmlContent)) {
            throw new Exception("El contenido provisto no es un XML válido.");
        }

        // 3. Setup XMLDSig DSig object (Empty prefix is crucial for DGII .NET parser)
        $dsig = new XMLSecurityDSig('');
        $dsig->setCanonicalMethod(XMLSecurityDSig::C14N);
        
        // Add Enveloped reference with SHA256
        $dsig->addReference(
            $doc,
            XMLSecurityDSig::SHA256,
            [
                'http://www.w3.org/2000/09/xmldsig#enveloped-signature'
            ],
            [
                'force_uri' => true
            ]
        );

        // 4. Create and load the private RSA-SHA256 signing key
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $objKey->loadKey($privateKey, false);

        // 5. Sign the document
        $dsig->sign($objKey);

        // 6. Attach public certificate (X509) to the signature
        $dsig->add509Cert($publicKey);

        // 7. Insert the signature element into the root element of the document
        $dsig->insertSignature($doc->documentElement);

        // 8. Return signed XML
        return $doc->saveXML();
    }

    /**
     * Extracts the security code (first 6 characters of the signature value hash)
     * required for the printed representation QR Code.
     *
     * @param string $signedXmlContent Signed XML content.
     * @return string 6-character security code or a fallback.
     */
    public function getSecurityCode(string $signedXmlContent): string
    {
        try {
            $doc = new DOMDocument();
            if ($doc->loadXML($signedXmlContent)) {
                $signatureValueNode = $doc->getElementsByTagName('SignatureValue')->item(0);
                if ($signatureValueNode) {
                    $signatureValue = trim($signatureValueNode->textContent);
                    // MD5 or SHA-1 hash of the raw SignatureValue and extract first 6 characters
                    $hash = md5($signatureValue);
                    return substr($hash, 0, 6);
                }
            }
        } catch (Exception $e) {
            Log::error("[XmlSignatureService] Error al extraer el código de seguridad: " . $e->getMessage());
        }

        return '000000';
    }
}

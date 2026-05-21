<?php

namespace App\Services\Dgii;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Signs XML documents using XMLDSig Enveloped Signature for DGII e-CF.
 * 
 * Implementation follows the official DGII guide "Firmado de e-CF.pdf" exactly:
 * - SHA256 for SignatureMethod and DigestMethod
 * - C14N(false, false) canonicalization (no exclusive, no comments)
 * - Enveloped-signature transform
 * - Only X509Certificate in KeyInfo (no KeyValue/RSAKeyValue/Exponent)
 * - preserveWhiteSpace = false on DOMDocument
 * - Reference URI="" (empty — signs the whole document)
 */
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

        // 1. Extract private key and certificate from P12
        $p12Content = file_get_contents($p12Path);
        $certs = [];

        if (!openssl_pkcs12_read($p12Content, $certs, $password)) {
            throw new Exception("Error al leer el certificado digital (.p12). Verifique que la contraseña sea correcta.");
        }

        $privateKey = $certs['pkey'] ?? null;
        $publicCert = $certs['cert'] ?? null;

        if (!$privateKey || !$publicCert) {
            throw new Exception("No se pudo extraer la clave privada o el certificado del archivo .p12.");
        }

        // 2. Load XML document — DGII requires preserveWhiteSpace = false
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        if (!$doc->loadXML($xmlContent)) {
            throw new Exception("El contenido provisto no es un XML válido.");
        }

        if (!$doc->documentElement) {
            throw new Exception("El documento XML no tiene un elemento raíz.");
        }

        // 3. Canonicalize document content for digest — DGII requires C14N(false, false)
        $canonicalData = $doc->documentElement->C14N(false, false);

        // 4. Calculate SHA-256 digest
        $digestValue = base64_encode(hash('sha256', $canonicalData, true));

        // 5. Build the Signature element structure (following DGII spec exactly)
        $dsigNs = 'http://www.w3.org/2000/09/xmldsig#';

        $signatureElement = $doc->createElement('Signature');
        $signatureElement->setAttribute('xmlns', $dsigNs);

        // --- SignedInfo ---
        $signedInfoElement = $doc->createElement('SignedInfo');
        $signatureElement->appendChild($signedInfoElement);

        $canonMethodElement = $doc->createElement('CanonicalizationMethod');
        $canonMethodElement->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
        $signedInfoElement->appendChild($canonMethodElement);

        $sigMethodElement = $doc->createElement('SignatureMethod');
        $sigMethodElement->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');
        $signedInfoElement->appendChild($sigMethodElement);

        // --- Reference ---
        $referenceElement = $doc->createElement('Reference');
        $referenceElement->setAttribute('URI', '');
        $signedInfoElement->appendChild($referenceElement);

        $transformsElement = $doc->createElement('Transforms');
        $referenceElement->appendChild($transformsElement);

        $transformElement = $doc->createElement('Transform');
        $transformElement->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $transformsElement->appendChild($transformElement);

        $digestMethodElement = $doc->createElement('DigestMethod');
        $digestMethodElement->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
        $referenceElement->appendChild($digestMethodElement);

        $digestValueElement = $doc->createElement('DigestValue', $digestValue);
        $referenceElement->appendChild($digestValueElement);

        // --- SignatureValue (placeholder, computed after canonicalization) ---
        $signatureValueElement = $doc->createElement('SignatureValue', '');
        $signatureElement->appendChild($signatureValueElement);

        // --- KeyInfo with ONLY X509Certificate (NO KeyValue/RSAKeyValue per DGII guide) ---
        $keyInfoElement = $doc->createElement('KeyInfo');
        $signatureElement->appendChild($keyInfoElement);

        $x509DataElement = $doc->createElement('X509Data');
        $keyInfoElement->appendChild($x509DataElement);

        // Strip PEM headers to get raw base64 cert
        $rawCert = str_replace(
            ['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n", ' '],
            '',
            $publicCert
        );
        $x509CertElement = $doc->createElement('X509Certificate', $rawCert);
        $x509DataElement->appendChild($x509CertElement);

        // 6. Append signature to document root
        $doc->documentElement->appendChild($signatureElement);

        // 7. Canonicalize SignedInfo and compute RSA-SHA256 signature — DGII requires C14N(false, false)
        $c14nSignedInfo = $signedInfoElement->C14N(false, false);

        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if (!$privateKeyResource) {
            throw new Exception("No se pudo cargar la clave privada del certificado.");
        }

        $signatureRaw = '';
        if (!openssl_sign($c14nSignedInfo, $signatureRaw, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
            throw new Exception("Error al generar la firma digital RSA-SHA256.");
        }

        // 8. Set the computed signature value
        $signatureValueElement->nodeValue = base64_encode($signatureRaw);

        // 9. Return signed XML
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
                    // DGII requires the first 6 characters of the SignatureValue (base64) directly
                    return substr($signatureValue, 0, 6);
                }
            }
        } catch (Exception $e) {
            Log::error("[XmlSignatureService] Error al extraer el código de seguridad: " . $e->getMessage());
        }

        return '000000';
    }
}

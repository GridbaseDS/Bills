<?php

namespace App\Services\Auth;

use Exception;

class WebAuthnService
{
    /**
     * Generate a cryptographically secure random challenge string.
     */
    public function generateChallenge(): string
    {
        return $this->base64UrlEncode(random_bytes(32));
    }

    /**
     * Decode Base64URL string to raw binary string.
     */
    public function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Encode binary string to Base64URL string.
     */
    public function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Extract Credential ID and OpenSSL PEM Public Key from attestationObject.
     */
    public function parseAttestationObject(string $attestationObjectBase64Url): array
    {
        $rawAttestation = $this->base64UrlDecode($attestationObjectBase64Url);
        $decoded = $this->cborDecode($rawAttestation);

        if (!isset($decoded['authData'])) {
            throw new Exception('Objeto de atestación WebAuthn no válido.');
        }

        $authData = $decoded['authData'];
        $authDataLen = strlen($authData);

        if ($authDataLen < 37) {
            throw new Exception('Datos de autenticador demasiado cortos.');
        }

        $flags = ord($authData[32]);
        $signCount = unpack('N', substr($authData, 33, 4))[1];

        // Check AT flag (Attested Credential Data Present) -> 0x40
        if (!($flags & 0x40)) {
            throw new Exception('Falta información de credencial en la respuesta del dispositivo.');
        }

        // Credential Data starts at offset 37
        // AAGUID: 16 bytes (37 to 52)
        // Credential ID Length: 2 bytes uint16 (53 to 54)
        $credIdLen = unpack('n', substr($authData, 53, 2))[1];
        $credentialIdBin = substr($authData, 55, $credIdLen);
        $credentialIdBase64Url = $this->base64UrlEncode($credentialIdBin);

        // COSE Public Key starts at offset 55 + $credIdLen
        $cosePublicKeyBin = substr($authData, 55 + $credIdLen);
        $coseKey = $this->cborDecode($cosePublicKeyBin);

        $pemPublicKey = $this->coseToPem($coseKey);

        return [
            'credential_id' => $credentialIdBase64Url,
            'public_key' => $pemPublicKey,
            'sign_count' => $signCount,
        ];
    }

    /**
     * Convert COSE Key Map to OpenSSL PEM Public Key string.
     */
    public function coseToPem(array $coseKey): string
    {
        // Check EC2 algorithm (-7 is ES256) or RSA (-257)
        $kty = $coseKey[1] ?? null;
        $alg = $coseKey[3] ?? null;

        if ($kty === 2) { // EC2
            $x = $coseKey[-2] ?? null;
            $y = $coseKey[-3] ?? null;

            if (!$x || !$y || strlen($x) !== 32 || strlen($y) !== 32) {
                throw new Exception('Coordenadas de clave EC WebAuthn no válidas.');
            }

            // DER Header for EC P-256 SubjectPublicKeyInfo (91 bytes total)
            $derHeader = pack('H*', '3059301306072a8648ce3d020106082a8648ce3d03010703420004');
            $der = $derHeader . $x . $y;

            $pem = "-----BEGIN PUBLIC KEY-----\n";
            $pem .= chunk_split(base64_encode($der), 64, "\n");
            $pem .= "-----END PUBLIC KEY-----";

            return $pem;
        } elseif ($kty === 3) { // RSA
            $n = $coseKey[-1] ?? null;
            $e = $coseKey[-2] ?? null;

            if (!$n || !$e) {
                throw new Exception('Parámetros de clave RSA WebAuthn no válidos.');
            }

            // Convert RSA n & e to DER format
            $modulusDER = $this->asn1Integer($n);
            $exponentDER = $this->asn1Integer($e);
            $rsaPubKeyDER = pack('H*', '30') . $this->asn1Length(strlen($modulusDER . $exponentDER)) . $modulusDER . $exponentDER;

            // AlgorithmIdentifier for rsaEncryption: 1.2.840.113549.1.1.1
            $algIdDER = pack('H*', '300d06092a864886f70d0101010500');
            $bitStringDER = pack('H*', '03') . $this->asn1Length(strlen($rsaPubKeyDER) + 1) . "\x00" . $rsaPubKeyDER;
            $spkiDER = pack('H*', '30') . $this->asn1Length(strlen($algIdDER . $bitStringDER)) . $algIdDER . $bitStringDER;

            $pem = "-----BEGIN PUBLIC KEY-----\n";
            $pem .= chunk_split(base64_encode($spkiDER), 64, "\n");
            $pem .= "-----END PUBLIC KEY-----";

            return $pem;
        }

        throw new Exception('Algoritmo de clave biométrica no soportado.');
    }

    /**
     * Verify WebAuthn Assertion (Login Signature).
     */
    public function verifyAssertion(
        string $authenticatorDataBase64Url,
        string $clientDataJsonBase64Url,
        string $signatureBase64Url,
        string $pemPublicKey,
        string $expectedChallenge,
        string $expectedOrigin
    ): bool {
        $authenticatorData = $this->base64UrlDecode($authenticatorDataBase64Url);
        $clientDataJson = $this->base64UrlDecode($clientDataJsonBase64Url);
        $signature = $this->base64UrlDecode($signatureBase64Url);

        // 1. Verify ClientDataJSON challenge & origin
        $clientData = json_decode($clientDataJson, true);
        if (!$clientData || !isset($clientData['challenge'])) {
            throw new Exception('ClientDataJSON biométrico no válido.');
        }

        if ($clientData['challenge'] !== $expectedChallenge) {
            throw new Exception('El desafío biométrico no coincide o ha expirado.');
        }

        // Clean origins (strip port if matching domain)
        $receivedOrigin = parse_url($clientData['origin'] ?? '', PHP_URL_HOST);
        $expectedHost = parse_url($expectedOrigin, PHP_URL_HOST) ?: $expectedOrigin;

        if ($receivedOrigin && $expectedHost && $receivedOrigin !== $expectedHost && $receivedOrigin !== 'localhost' && $receivedOrigin !== '127.0.0.1') {
            throw new Exception("Origen no autorizado: {$clientData['origin']}");
        }

        // 2. Compute signed data (authenticatorData + sha256(clientDataJSON))
        $clientDataHash = hash('sha256', $clientDataJson, true);
        $signedData = $authenticatorData . $clientDataHash;

        // 3. Verify signature with OpenSSL
        $verifyResult = openssl_verify($signedData, $signature, $pemPublicKey, OPENSSL_ALGO_SHA256);

        return $verifyResult === 1;
    }

    // Helper: ASN.1 Integer encoding
    private function asn1Integer(string $bytes): string
    {
        if (ord($bytes[0]) > 0x7f) {
            $bytes = "\x00" . $bytes;
        }
        return "\x02" . $this->asn1Length(strlen($bytes)) . $bytes;
    }

    // Helper: ASN.1 Length encoding
    private function asn1Length(int $len): string
    {
        if ($len < 128) {
            return chr($len);
        }
        $lenBytes = ltrim(pack('N', $len), "\x00");
        return chr(0x80 | strlen($lenBytes)) . $lenBytes;
    }

    /**
     * Minimal CBOR decoder in pure PHP for WebAuthn COSE structure.
     */
    public function cborDecode(string $data, int &$offset = 0)
    {
        if ($offset >= strlen($data)) {
            return null;
        }

        $byte = ord($data[$offset++]);
        $major = $byte >> 5;
        $val = $byte & 0x1f;

        if ($val === 24) {
            $val = ord($data[$offset++]);
        } elseif ($val === 25) {
            $val = unpack('n', substr($data, $offset, 2))[1];
            $offset += 2;
        } elseif ($val === 26) {
            $val = unpack('N', substr($data, $offset, 4))[1];
            $offset += 4;
        } elseif ($val === 27) {
            $val = unpack('J', substr($data, $offset, 8))[1];
            $offset += 8;
        }

        switch ($major) {
            case 0: // unsigned int
                return $val;
            case 1: // negative int
                return -1 - $val;
            case 2: // byte string
                $str = substr($data, $offset, $val);
                $offset += $val;
                return $str;
            case 3: // text string
                $str = substr($data, $offset, $val);
                $offset += $val;
                return $str;
            case 4: // array
                $arr = [];
                for ($i = 0; $i < $val; $i++) {
                    $arr[] = $this->cborDecode($data, $offset);
                }
                return $arr;
            case 5: // map
                $map = [];
                for ($i = 0; $i < $val; $i++) {
                    $key = $this->cborDecode($data, $offset);
                    $map[$key] = $this->cborDecode($data, $offset);
                }
                return $map;
            case 7: // simple / float
                if ($val === 20) return false;
                if ($val === 21) return true;
                if ($val === 22) return null;
                return null;
            default:
                return null;
        }
    }
}

<?php

namespace App\Services\Dgii;

use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DgiiAuthService
{
    protected XmlSignatureService $signatureService;

    public function __construct(XmlSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Gets a valid DGII JWT Bearer Token, either from cache or by performing the signature auth flow.
     *
     * @param array $settings System settings array containing certificate details.
     * @return string Valid Bearer Token.
     * @throws Exception
     */
    public function getValidToken(array $settings): string
    {
        $env = $settings['dgii_env'] ?? 'testing';
        $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
        $cacheKey = "dgii_bearer_token_{$rncEmisor}_{$env}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        Log::info("[DgiiAuthService] Token de DGII expirado o inexistente. Iniciando flujo de firma de semilla.");
        
        $tokenData = $this->requestNewToken($settings);
        
        // Cache the token slightly less than expiration (DGII tokens last 24 hours, we cache for 23 hours)
        Cache::put($cacheKey, $tokenData['token'], now()->addHours(23));

        return $tokenData['token'];
    }

    /**
     * Executes the seed fetch -> sign -> validate token flow.
     *
     * @param array $settings System settings.
     * @return array Array containing 'token' and 'expira'.
     * @throws Exception
     */
    protected function requestNewToken(array $settings): array
    {
        $env = $settings['dgii_env'] ?? 'testing';
        $p12Path = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
        $p12Password = $settings['dgii_certificate_password'] ?? '';

        if (empty($settings['dgii_certificate_path']) || !file_exists($p12Path)) {
            throw new Exception("Certificado digital (.p12) no cargado o no encontrado en la ruta segura.");
        }

        // 1. Determine Endpoints
        // Testing (Certificación) vs Production
        $baseUrl = $env === 'production' 
            ? 'https://ecf.dgii.gov.do' 
            : 'https://ecf.dgii.gov.do/certecf';

        Log::info("[DgiiAuthService] Obteniendo Semilla de la DGII en entorno: {$env}");

        // 2. Fetch Semilla XML
        $semillaResponse = Http::withoutVerifying()
            ->timeout(10)
            ->get("{$baseUrl}/Autenticacion/api/Autenticacion/Semilla");

        if (!$semillaResponse->successful()) {
            throw new Exception("Error al conectar con la DGII para obtener Semilla XML. Status: " . $semillaResponse->status());
        }

        $semillaXml = $semillaResponse->body();
        
        Log::info("[DgiiAuthService] Semilla obtenida con éxito. Firmando XML Semilla.");

        // 3. Sign the Semilla XML
        $signedSemillaXml = $this->signatureService->signXml($semillaXml, $p12Path, $p12Password);

        Log::info("[DgiiAuthService] Semilla firmada con éxito. Solicitando Token Bearer JWT.");
        Log::info("Signed Semilla XML: \n" . $signedSemillaXml);

        // 4. Submit signed Semilla XML to get JWT Bearer Token (DGII requires multipart/form-data)
        $tokenResponse = Http::withoutVerifying()
            ->timeout(15)
            ->withHeaders([
                'Accept' => 'application/json'
            ])
            ->attach('xml', $signedSemillaXml, 'semilla.xml', ['Content-Type' => 'text/xml'])
            ->post("{$baseUrl}/Autenticacion/api/Autenticacion/ValidarSemilla");

        if (!$tokenResponse->successful()) {
            Log::error("[DgiiAuthService] Error al validar semilla en la DGII: " . $tokenResponse->body());
            throw new Exception("La DGII rechazó la firma digital de la Semilla. Verifique que su certificado esté vigente y el RNC coincida.");
        }

        $data = $tokenResponse->json();
        
        if (empty($data['token'])) {
            // Check if response is XML instead of JSON
            try {
                $xml = simplexml_load_string($tokenResponse->body());
                if (isset($xml->token)) {
                    $data = [
                        'token' => (string)$xml->token,
                        'expira' => (string)$xml->expira
                    ];
                }
            } catch (Exception $e) {
                throw new Exception("La respuesta de validación de semilla de la DGII no contiene un token válido.");
            }
        }

        if (empty($data['token'])) {
            throw new Exception("La respuesta de validación de semilla de la DGII no contiene un token válido.");
        }

        Log::info("[DgiiAuthService] Token obtenido exitosamente de la DGII.");

        return $data;
    }
}

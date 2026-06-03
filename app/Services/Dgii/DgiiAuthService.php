<?php

namespace App\Services\Dgii;

use App\Models\DgiiLog;
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
     */
    public function getValidToken(array $settings, ?int $invoiceId = null, ?string $encf = null, ?string $ecfType = null): string
    {
        $env = $settings['dgii_env'] ?? 'testing';
        $rncEmisor = preg_replace('/[^0-9]/', '', $settings['company_tax_id'] ?? '');
        $cacheKey = "dgii_bearer_token_{$rncEmisor}_{$env}";

        if (Cache::has($cacheKey)) {
            $token = Cache::get($cacheKey);
            DgiiLog::logStep('auth_cache_hit', "Token DGII obtenido del caché (válido)", $invoiceId, $encf, $ecfType, [
                'context' => [
                    'cache_key' => $cacheKey,
                    'token_length' => strlen($token),
                    'token_prefix' => substr($token, 0, 20) . '...',
                ],
            ]);
            return $token;
        }

        Log::info("[DgiiAuthService] Token de DGII expirado o inexistente. Iniciando flujo de firma de semilla.");

        DgiiLog::logStep('auth_cache_miss', "Token no en caché. Iniciando flujo Semilla → Firma → Token.", $invoiceId, $encf, $ecfType, [
            'context' => ['cache_key' => $cacheKey, 'env' => $env, 'rnc' => $rncEmisor],
        ]);
        
        $tokenData = $this->requestNewToken($settings, $invoiceId, $encf, $ecfType);
        
        // Cache the token slightly less than expiration (DGII tokens last 24 hours, we cache for 23 hours)
        Cache::put($cacheKey, $tokenData['token'], now()->addHours(23));

        return $tokenData['token'];
    }

    /**
     * Executes the seed fetch -> sign -> validate token flow.
     */
    protected function requestNewToken(array $settings, ?int $invoiceId = null, ?string $encf = null, ?string $ecfType = null): array
    {
        $env = $settings['dgii_env'] ?? 'testing';
        $p12Path = storage_path('app/secure/' . ($settings['dgii_certificate_path'] ?? ''));
        $p12Password = $settings['dgii_certificate_password'] ?? '';

        if (empty($settings['dgii_certificate_path']) || !file_exists($p12Path)) {
            DgiiLog::logStep('auth_cert_missing', "CRÍTICO: Certificado .p12 no encontrado", $invoiceId, $encf, $ecfType, [
                'level' => 'critical',
                'context' => ['path' => $p12Path, 'exists' => file_exists($p12Path)],
            ]);
            throw new Exception("Certificado digital (.p12) no cargado o no encontrado en la ruta segura.");
        }

        // 1. Determine Endpoints
        $baseUrl = $env === 'production' 
            ? 'https://ecf.dgii.gov.do' 
            : 'https://ecf.dgii.gov.do/certecf';

        Log::info("[DgiiAuthService] Obteniendo Semilla de la DGII en entorno: {$env}");

        // 2. Fetch Semilla XML
        $startSemilla = microtime(true);
        $semillaUrl = "{$baseUrl}/Autenticacion/api/Autenticacion/Semilla";
        $semillaResponse = Http::withoutVerifying()
            ->timeout(10)
            ->get($semillaUrl);
        $semillaDuration = round((microtime(true) - $startSemilla) * 1000, 1);

        DgiiLog::logHttp(
            'auth_semilla_fetch',
            "Semilla obtenida de DGII → HTTP {$semillaResponse->status()} en {$semillaDuration}ms",
            'GET',
            $semillaUrl,
            $semillaResponse->status(),
            null,
            substr($semillaResponse->body(), 0, 500),
            $semillaDuration,
            $invoiceId, $encf, $ecfType,
            $semillaResponse->successful() ? 'info' : 'error'
        );

        if (!$semillaResponse->successful()) {
            throw new Exception("Error al conectar con la DGII para obtener Semilla XML. Status: " . $semillaResponse->status());
        }

        $semillaXml = $semillaResponse->body();
        
        Log::info("[DgiiAuthService] Semilla obtenida con éxito. Firmando XML Semilla.");

        // 3. Sign the Semilla XML
        $signedSemillaXml = $this->signatureService->signXml($semillaXml, $p12Path, $p12Password);

        $hasSig = strpos($signedSemillaXml, '<SignatureValue>') !== false;
        DgiiLog::logStep('auth_semilla_signed', "Semilla firmada. Firma presente: " . ($hasSig ? 'SÍ' : 'NO') . ". Longitud: " . strlen($signedSemillaXml), $invoiceId, $encf, $ecfType, [
            'level' => $hasSig ? 'info' : 'critical',
            'context' => [
                'signed_length' => strlen($signedSemillaXml),
                'has_signature' => $hasSig,
            ],
        ]);

        Log::info("[DgiiAuthService] Semilla firmada con éxito. Solicitando Token Bearer JWT.");

        // 4. Submit signed Semilla XML to get JWT Bearer Token
        $startToken = microtime(true);
        $tokenUrl = "{$baseUrl}/Autenticacion/api/Autenticacion/ValidarSemilla";
        $tokenResponse = Http::withoutVerifying()
            ->timeout(15)
            ->withHeaders([
                'Accept' => 'application/json'
            ])
            ->attach('xml', $signedSemillaXml, 'semilla.xml', ['Content-Type' => 'text/xml'])
            ->post($tokenUrl);
        $tokenDuration = round((microtime(true) - $startToken) * 1000, 1);

        DgiiLog::logHttp(
            'auth_token_response',
            "ValidarSemilla → HTTP {$tokenResponse->status()} en {$tokenDuration}ms",
            'POST',
            $tokenUrl,
            $tokenResponse->status(),
            "Signed semilla (" . strlen($signedSemillaXml) . " bytes)",
            $tokenResponse->body(),
            $tokenDuration,
            $invoiceId, $encf, $ecfType,
            $tokenResponse->successful() ? 'info' : 'error'
        );

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
            DgiiLog::logStep('auth_token_missing', "CRÍTICO: DGII respondió OK pero sin token", $invoiceId, $encf, $ecfType, [
                'level' => 'critical',
                'context' => ['raw_body' => $tokenResponse->body()],
            ]);
            throw new Exception("La respuesta de validación de semilla de la DGII no contiene un token válido.");
        }

        DgiiLog::logStep('auth_token_obtained', "✓ Token JWT obtenido exitosamente de la DGII", $invoiceId, $encf, $ecfType, [
            'context' => [
                'token_length' => strlen($data['token']),
                'token_prefix' => substr($data['token'], 0, 30) . '...',
                'expira' => $data['expira'] ?? 'N/A',
                'total_auth_time_ms' => $semillaDuration + $tokenDuration,
            ],
        ]);

        Log::info("[DgiiAuthService] Token obtenido exitosamente de la DGII.");

        return $data;
    }
}

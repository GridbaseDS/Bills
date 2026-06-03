<?php

namespace App\Services\Dgii;

use App\Models\DgiiLog;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DgiiApiService
{
    /**
     * Submits a signed e-CF XML document to the DGII reception endpoint.
     * Uses multipart/form-data with the filename format: {RNCEmisor}{eNCF}.xml
     */
    public function submitInvoice(string $signedXml, string $token, string $env, bool $isRfce = false, ?int $invoiceId = null, ?string $encf = null, ?string $ecfType = null): array
    {
        // DGII Environments: testecf=pre-certification (free testing), certecf=formal certification, ecf=production
        $envPath = match($env) {
            'production' => 'ecf',
            'certification' => 'certecf',
            default => 'testecf',  // testing/pre-certification
        };
        $ecfBaseUrl = "https://ecf.dgii.gov.do/{$envPath}";
        $fcBaseUrl = "https://fc.dgii.gov.do/{$envPath}";

        $endpoint = $isRfce 
            ? "$fcBaseUrl/recepcionfc/api/recepcion/ecf" 
            : "$ecfBaseUrl/recepcion/api/facturaselectronicas";

        // CRITICAL: Filename MUST be {RNCEmisor}{eNCF}.xml
        preg_match('/<RNCEmisor>(\d+)<\/RNCEmisor>/', $signedXml, $rncMatch);
        preg_match('/<eNCF>([^<]+)<\/eNCF>/', $signedXml, $encfMatch);
        $rncEmisor = $rncMatch[1] ?? '000000000';
        $xmlEncf = $encfMatch[1] ?? 'E000000000000';
        $sendFilename = "{$rncEmisor}{$xmlEncf}.xml";

        DgiiLog::logStep('submit_prepare', "Preparando envío " . ($isRfce ? 'RFCE' : 'e-CF') . " a DGII", $invoiceId, $encf, $ecfType, [
            'context' => [
                'endpoint' => $endpoint,
                'filename' => $sendFilename,
                'is_rfce' => $isRfce,
                'env' => $env,
                'xml_length' => strlen($signedXml),
                'token_length' => strlen($token),
                'token_prefix' => substr($token, 0, 20) . '...',
                'has_signature' => strpos($signedXml, '<SignatureValue>') !== false,
            ],
        ]);

        Log::info("[DgiiApiService] Transmitiendo " . ($isRfce ? 'RFCE' : 'e-CF') . " a {$endpoint} como {$sendFilename}");

        try {
            $startTime = microtime(true);

            // DGII requires multipart/form-data with 'xml' field (NOT raw body)
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->attach('xml', $signedXml, $sendFilename, ['Content-Type' => 'text/xml'])
                ->post($endpoint);

            $durationMs = round((microtime(true) - $startTime) * 1000, 1);
            $responseBody = $response->json();
            $rawBody = $response->body();

            // Log the raw HTTP exchange
            DgiiLog::logHttp(
                'submit_response',
                "DGII respondió HTTP {$response->status()} en {$durationMs}ms",
                'POST',
                $endpoint,
                $response->status(),
                "Filename: {$sendFilename}, XML length: " . strlen($signedXml),
                $rawBody,
                $durationMs,
                $invoiceId,
                $encf,
                $ecfType,
                $response->successful() ? 'info' : 'error',
                [
                    'context' => [
                        'response_json' => $responseBody,
                        'response_headers' => $response->headers() ?? [],
                    ],
                ]
            );

            if (!$response->successful()) {
                Log::error("[DgiiApiService] Error DGII. Status: {$response->status()}, Body: {$rawBody}");
                
                DgiiLog::logStep('submit_http_error', "DGII devolvió HTTP {$response->status()}", $invoiceId, $encf, $ecfType, [
                    'level' => 'error',
                    'dgii_status' => 'contingency',
                    'context' => [
                        'http_status' => $response->status(),
                        'body' => $rawBody,
                        'parsed' => $responseBody,
                    ],
                ]);

                return [
                    'success' => false,
                    'status' => 'contingency',
                    'track_id' => null,
                    'errors' => "HTTP {$response->status()}: " . $rawBody
                ];
            }

            // e-CF response: {"trackId": "uuid"} — trackId means DGII accepted the document
            if (isset($responseBody['trackId'])) {
                Log::info("[DgiiApiService] e-CF aceptado. TrackId: {$responseBody['trackId']}");
                
                DgiiLog::logStep('submit_accepted', "✓ e-CF ACEPTADO por DGII. TrackId: {$responseBody['trackId']}", $invoiceId, $encf, $ecfType, [
                    'dgii_status' => 'accepted',
                    'dgii_track_id' => $responseBody['trackId'],
                    'context' => [
                        'full_response' => $responseBody,
                        'duration_ms' => $durationMs,
                    ],
                ]);

                return [
                    'success' => true,
                    'status' => 'accepted',
                    'track_id' => $responseBody['trackId'],
                    'errors' => null
                ];
            }

            // RFCE response: {"codigo": 1, "estado": "Aceptado", "encf": "E32..."}
            if (isset($responseBody['codigo']) && $responseBody['codigo'] == 1) {
                Log::info("[DgiiApiService] RFCE aceptado. Estado: " . ($responseBody['estado'] ?? 'OK'));

                DgiiLog::logStep('submit_rfce_accepted', "✓ RFCE ACEPTADO. Código: {$responseBody['codigo']}", $invoiceId, $encf, $ecfType, [
                    'dgii_status' => 'accepted',
                    'dgii_track_id' => $responseBody['encf'] ?? 'RFCE_ACCEPTED',
                    'context' => ['full_response' => $responseBody],
                ]);

                return [
                    'success' => true,
                    'status' => 'accepted',
                    'track_id' => $responseBody['encf'] ?? 'RFCE_ACCEPTED',
                    'errors' => null
                ];
            }

            // Synchronous acceptance
            if (isset($responseBody['estado']) && strtolower($responseBody['estado']) === 'aceptado') {
                DgiiLog::logStep('submit_sync_accepted', "✓ Aceptado sincrónicamente", $invoiceId, $encf, $ecfType, [
                    'dgii_status' => 'accepted',
                    'dgii_track_id' => 'SYNC_APPROVED',
                    'context' => ['full_response' => $responseBody],
                ]);

                return [
                    'success' => true,
                    'status' => 'accepted',
                    'track_id' => 'SYNC_APPROVED',
                    'errors' => null
                ];
            }

            // Rejection
            if (isset($responseBody['estado']) && strtolower($responseBody['estado']) === 'rechazado') {
                $errMessages = $responseBody['mensajes'] ?? 'Rechazado por la DGII.';
                $errStr = is_array($errMessages) ? json_encode($errMessages, JSON_UNESCAPED_UNICODE) : $errMessages;

                DgiiLog::logStep('submit_rejected', "✗ RECHAZADO por DGII: {$errStr}", $invoiceId, $encf, $ecfType, [
                    'level' => 'error',
                    'dgii_status' => 'rejected',
                    'dgii_error_messages' => $errStr,
                    'context' => ['full_response' => $responseBody],
                ]);

                return [
                    'success' => false,
                    'status' => 'rejected',
                    'track_id' => null,
                    'errors' => $errMessages
                ];
            }

            // Unknown response format
            Log::warning("[DgiiApiService] Respuesta no reconocida: " . $rawBody);

            DgiiLog::logStep('submit_unknown', "⚠ Respuesta DGII no reconocida", $invoiceId, $encf, $ecfType, [
                'level' => 'warning',
                'dgii_status' => 'contingency',
                'context' => [
                    'raw_body' => $rawBody,
                    'parsed' => $responseBody,
                ],
            ]);

            return [
                'success' => false,
                'status' => 'contingency',
                'track_id' => null,
                'errors' => 'Respuesta no reconocida de la DGII: ' . $rawBody
            ];

        } catch (Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 1);

            Log::error("[DgiiApiService] Excepción: " . $e->getMessage());

            DgiiLog::logStep('submit_exception', "EXCEPCIÓN al enviar a DGII: {$e->getMessage()}", $invoiceId, $encf, $ecfType, [
                'level' => 'critical',
                'context' => [
                    'exception' => $e->getMessage(),
                    'duration_ms' => $durationMs,
                    'endpoint' => $endpoint,
                ],
            ]);

            return [
                'success' => false,
                'status' => 'contingency',
                'track_id' => null,
                'errors' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Polls the validation status of a previously submitted e-CF.
     */
    public function queryInvoiceStatus(string $trackId, string $token, string $env): array
    {
        if (in_array($trackId, ['SYNC_APPROVED', 'RFCE_ACCEPTED'])) {
            return [
                'status' => 'accepted',
                'errors' => null
            ];
        }

        $envPath = match($env) {
            'production' => 'ecf',
            'certification' => 'certecf',
            default => 'testecf',
        };
        $baseUrl = "https://ecf.dgii.gov.do/{$envPath}";

        try {
            $startTime = microtime(true);
            $url = "{$baseUrl}/consultaresultado/api/consultas/estado?trackid={$trackId}";

            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            $durationMs = round((microtime(true) - $startTime) * 1000, 1);

            DgiiLog::logHttp(
                'status_check',
                "Consulta estado trackId: {$trackId} → HTTP {$response->status()}",
                'GET',
                $url,
                $response->status(),
                null,
                $response->body(),
                $durationMs
            );

            if (!$response->successful()) {
                return [
                    'status' => 'pending',
                    'errors' => "No se pudo contactar DGII. HTTP {$response->status()}"
                ];
            }

            $data = $response->json();
            $codigo = $data['codigo'] ?? null;
            $estado = strtolower($data['estado'] ?? $data['Estado'] ?? '');

            // DGII codes: 0=Not found, 1=Accepted, 2=Rejected, 3=In Process, 4=Accepted Conditional
            if ($codigo === 1 || $estado === 'aceptado') {
                return ['status' => 'accepted', 'errors' => null];
            }

            if ($codigo === 4 || $estado === 'aceptado condicional') {
                return ['status' => 'accepted', 'errors' => 'Aceptado condicional'];
            }

            if ($codigo === 2 || $estado === 'rechazado') {
                $mensajes = $data['mensajes'] ?? $data['Mensajes'] ?? [];
                // mensajes can be array of {codigo, valor} objects
                if (is_array($mensajes)) {
                    $errorParts = [];
                    foreach ($mensajes as $msg) {
                        if (is_array($msg) && isset($msg['valor'])) {
                            $errorParts[] = ($msg['codigo'] ?? '') . ': ' . $msg['valor'];
                        } elseif (is_string($msg)) {
                            $errorParts[] = $msg;
                        }
                    }
                    $errorStr = implode(' | ', $errorParts);
                } else {
                    $errorStr = (string)$mensajes;
                }
                return [
                    'status' => 'rejected',
                    'errors' => $errorStr ?: 'Rechazado por la DGII.'
                ];
            }

            if ($codigo === 3 || $estado === 'en proceso') {
                return ['status' => 'pending', 'errors' => null];
            }

            if ($codigo === 0 || $estado === 'no encontrado') {
                return ['status' => 'not_found', 'errors' => 'e-CF no encontrado en registros DGII'];
            }

            return ['status' => 'pending', 'errors' => null];

        } catch (Exception $e) {
            Log::error("[DgiiApiService] Error consultando estado de {$trackId}: " . $e->getMessage());
            return [
                'status' => 'pending',
                'errors' => $e->getMessage()
            ];
        }
    }

    /**
     * Submits a signed ACECF (Aprobacion Comercial) XML to the DGII.
     * Endpoint: /AprobacionComercial
     */
    public function submitAprobacionComercial(string $signedXml, string $token, string $env): array
    {
        $envPath = match($env) {
            'production' => 'ecf',
            'certification' => 'certecf',
            default => 'testecf',
        };
        $baseUrl = "https://ecf.dgii.gov.do/{$envPath}";

        $endpoint = "{$baseUrl}/AprobacionComercial/api/AprobacionComercial";

        // Extract RNC and eNCF from XML for filename
        preg_match('/<RNCComprador>(\d+)<\/RNCComprador>/', $signedXml, $rncMatch);
        preg_match('/<eNCF>([^<]+)<\/eNCF>/', $signedXml, $encfMatch);
        $rnc = $rncMatch[1] ?? '000000000';
        $encf = $encfMatch[1] ?? 'E000000000000';
        $sendFilename = "{$rnc}{$encf}.xml";

        Log::info("[DgiiApiService] Enviando Aprobación Comercial a {$endpoint} como {$sendFilename}");

        try {
            $startTime = microtime(true);

            $response = Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->attach('xml', $signedXml, $sendFilename, ['Content-Type' => 'text/xml'])
                ->post($endpoint);

            $durationMs = round((microtime(true) - $startTime) * 1000, 1);
            $body = $response->body();
            $json = $response->json();

            DgiiLog::logHttp(
                'acecf_response',
                "ACECF Response: HTTP {$response->status()}",
                'POST',
                $endpoint,
                $response->status(),
                "Filename: {$sendFilename}",
                $body,
                $durationMs
            );

            Log::info("[DgiiApiService] ACECF Response: HTTP {$response->status()} - {$body}");

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'errors' => "HTTP {$response->status()}: {$body}",
                ];
            }

            // Success: trackId or aceptado
            if (isset($json['trackId'])) {
                return ['success' => true, 'track_id' => $json['trackId'], 'errors' => null];
            }
            if (isset($json['estado']) && strtolower($json['estado']) === 'aceptado') {
                return ['success' => true, 'track_id' => 'ACECF_ACCEPTED', 'errors' => null];
            }
            if (isset($json['codigo']) && $json['codigo'] == 1) {
                return ['success' => true, 'track_id' => 'ACECF_ACCEPTED', 'errors' => null];
            }

            // Rejection
            if (isset($json['estado']) && strtolower($json['estado']) === 'rechazado') {
                return ['success' => false, 'track_id' => null, 'errors' => $json['mensajes'] ?? 'Rechazado'];
            }

            return ['success' => false, 'errors' => "Respuesta no reconocida: {$body}"];

        } catch (Exception $e) {
            Log::error("[DgiiApiService] ACECF Exception: " . $e->getMessage());
            return ['success' => false, 'errors' => $e->getMessage()];
        }
    }
}

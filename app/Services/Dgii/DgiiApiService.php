<?php

namespace App\Services\Dgii;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DgiiApiService
{
    /**
     * Submits a signed e-CF XML document to the DGII reception endpoint.
     * Uses multipart/form-data with the filename format: {RNCEmisor}{eNCF}.xml
     *
     * @param string $signedXml Signed e-CF XML content.
     * @param string $token Valid DGII JWT Bearer Token.
     * @param string $env Environment: 'testing' or 'production'.
     * @param bool $isRfce Whether this is an RFCE (Resumen Factura Consumo Electrónico).
     * @return array Standardized response metadata (success, track_id, status, errors).
     */
    public function submitInvoice(string $signedXml, string $token, string $env, bool $isRfce = false): array
    {
        // Endpoints verified during DGII certification (May 2025)
        $ecfBaseUrl = $env === 'production'
            ? 'https://ecf.dgii.gov.do/ecf'
            : 'https://ecf.dgii.gov.do/certecf';
        $fcBaseUrl = $env === 'production'
            ? 'https://fc.dgii.gov.do/ecf'
            : 'https://fc.dgii.gov.do/certecf';

        $endpoint = $isRfce 
            ? "$fcBaseUrl/recepcionfc/api/recepcion/ecf" 
            : "$ecfBaseUrl/recepcion/api/facturaselectronicas";

        // CRITICAL: Filename MUST be {RNCEmisor}{eNCF}.xml
        preg_match('/<RNCEmisor>(\d+)<\/RNCEmisor>/', $signedXml, $rncMatch);
        preg_match('/<eNCF>([^<]+)<\/eNCF>/', $signedXml, $encfMatch);
        $rncEmisor = $rncMatch[1] ?? '000000000';
        $encf = $encfMatch[1] ?? 'E000000000000';
        $sendFilename = "{$rncEmisor}{$encf}.xml";

        Log::info("[DgiiApiService] Transmitiendo " . ($isRfce ? 'RFCE' : 'e-CF') . " a {$endpoint} como {$sendFilename}");

        try {
            // DGII requires multipart/form-data with 'xml' field (NOT raw body)
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->attach('xml', $signedXml, $sendFilename, ['Content-Type' => 'text/xml'])
                ->post($endpoint);

            $responseBody = $response->json();

            if (!$response->successful()) {
                Log::error("[DgiiApiService] Error DGII. Status: {$response->status()}, Body: {$response->body()}");
                
                return [
                    'success' => false,
                    'status' => 'contingency',
                    'track_id' => null,
                    'errors' => "HTTP {$response->status()}: " . $response->body()
                ];
            }

            // e-CF response: {"trackId": "uuid"}
            if (isset($responseBody['trackId'])) {
                Log::info("[DgiiApiService] e-CF aceptado. TrackId: {$responseBody['trackId']}");
                return [
                    'success' => true,
                    'status' => 'pending',
                    'track_id' => $responseBody['trackId'],
                    'errors' => null
                ];
            }

            // RFCE response: {"codigo": 1, "estado": "Aceptado", "encf": "E32..."}
            if (isset($responseBody['codigo']) && $responseBody['codigo'] == 1) {
                Log::info("[DgiiApiService] RFCE aceptado. Estado: " . ($responseBody['estado'] ?? 'OK'));
                return [
                    'success' => true,
                    'status' => 'accepted',
                    'track_id' => $responseBody['encf'] ?? 'RFCE_ACCEPTED',
                    'errors' => null
                ];
            }

            // Synchronous acceptance
            if (isset($responseBody['estado']) && strtolower($responseBody['estado']) === 'aceptado') {
                return [
                    'success' => true,
                    'status' => 'accepted',
                    'track_id' => 'SYNC_APPROVED',
                    'errors' => null
                ];
            }

            // Rejection
            if (isset($responseBody['estado']) && strtolower($responseBody['estado']) === 'rechazado') {
                return [
                    'success' => false,
                    'status' => 'rejected',
                    'track_id' => null,
                    'errors' => $responseBody['mensajes'] ?? 'Rechazado por la DGII.'
                ];
            }

            // Unknown response format
            Log::warning("[DgiiApiService] Respuesta no reconocida: " . $response->body());
            return [
                'success' => false,
                'status' => 'contingency',
                'track_id' => null,
                'errors' => 'Respuesta no reconocida de la DGII: ' . $response->body()
            ];

        } catch (Exception $e) {
            Log::error("[DgiiApiService] Excepción: " . $e->getMessage());
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
     *
     * @param string $trackId The reception identifier (trackId).
     * @param string $token Valid DGII JWT Bearer Token.
     * @param string $env Environment: 'testing' or 'production'.
     * @return array Standardized status metadata (status, errors).
     */
    public function queryInvoiceStatus(string $trackId, string $token, string $env): array
    {
        if (in_array($trackId, ['SYNC_APPROVED', 'RFCE_ACCEPTED'])) {
            return [
                'status' => 'accepted',
                'errors' => null
            ];
        }

        $baseUrl = $env === 'production'
            ? 'https://ecf.dgii.gov.do/ecf'
            : 'https://ecf.dgii.gov.do/certecf';

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->get("{$baseUrl}/recepcion/api/consultaresultado/{$trackId}");

            if (!$response->successful()) {
                return [
                    'status' => 'pending',
                    'errors' => "No se pudo contactar DGII. HTTP {$response->status()}"
                ];
            }

            $data = $response->json();
            $estado = strtolower($data['estado'] ?? $data['Estado'] ?? 'proceso');

            if ($estado === 'aceptado') {
                return ['status' => 'accepted', 'errors' => null];
            }

            if ($estado === 'rechazado') {
                $mensajes = $data['mensajes'] ?? $data['Mensajes'] ?? [];
                $errorStr = is_array($mensajes) ? implode(' | ', $mensajes) : (string)$mensajes;
                return [
                    'status' => 'rejected',
                    'errors' => $errorStr ?: 'Rechazado por la DGII.'
                ];
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
}

<?php

namespace App\Services\Dgii;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DgiiApiService
{
    /**
     * Submits a signed e-CF XML document to the DGII reception endpoint.
     *
     * @param string $signedXml Signed e-CF XML content.
     * @param string $token Valid DGII JWT Bearer Token.
     * @param string $env Environment: 'testing' or 'production'.
     * @return array Standardized response metadata (success, track_id, status, errors).
     * @throws Exception
     */
    public function submitInvoice(string $signedXml, string $token, string $env): array
    {
        $baseUrl = $env === 'production'
            ? 'https://ecf.dgii.gov.do/TCOrg/api'
            : 'https://ecf.dgii.gov.do/TestDGII/TCOrg/api';

        Log::info("[DgiiApiService] Transmitiendo e-CF a la DGII ({$env}).");

        $response = Http::withoutVerifying()
            ->timeout(20)
            ->withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/xml',
                'Accept' => 'application/json'
            ])
            ->send('POST', "{$baseUrl}/Recepcion/EnviarFactura", [
                'body' => $signedXml
            ]);

        if (!$response->successful()) {
            $errorBody = $response->body();
            Log::error("[DgiiApiService] Error de transmisión a DGII. Status: {$response->status()}, Body: {$errorBody}");
            
            // Return failure in a controlled manner (could trigger local contingency flow)
            return [
                'success' => false,
                'status' => 'contingency',
                'track_id' => null,
                'errors' => 'Error de conexión o rechazo de red por la DGII. Almacenado localmente en contingencia.'
            ];
        }

        $data = $response->json();
        $trackId = $data['recepcionId'] ?? $data['trackId'] ?? null;

        if (!$trackId) {
            // Handle case where DGII responds with synchronous validation
            if (isset($data['estado']) && strtolower($data['estado']) === 'aceptado') {
                return [
                    'success' => true,
                    'status' => 'accepted',
                    'track_id' => 'SYNC_APPROVED',
                    'errors' => null
                ];
            }

            if (isset($data['estado']) && strtolower($data['estado']) === 'rechazado') {
                return [
                    'success' => false,
                    'status' => 'rejected',
                    'track_id' => null,
                    'errors' => $data['mensajes'] ?? 'Rechazo inmediato por regla de negocio.'
                ];
            }

            // If no track_id could be parsed
            return [
                'success' => false,
                'status' => 'contingency',
                'track_id' => null,
                'errors' => 'No se recibió un Track ID de validación. La factura quedó en contingencia.'
            ];
        }

        Log::info("[DgiiApiService] Factura recibida de forma asíncrona por DGII. Track ID: {$trackId}");

        return [
            'success' => true,
            'status' => 'pending',
            'track_id' => $trackId,
            'errors' => null
        ];
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
        if ($trackId === 'SYNC_APPROVED') {
            return [
                'status' => 'accepted',
                'errors' => null
            ];
        }

        $baseUrl = $env === 'production'
            ? 'https://ecf.dgii.gov.do/TCOrg/api'
            : 'https://ecf.dgii.gov.do/TestDGII/TCOrg/api';

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withToken($token)
                ->get("{$baseUrl}/Recepcion/ConsultarEstado", [
                    'recepcionId' => $trackId
                ]);

            if (!$response->successful()) {
                return [
                    'status' => 'pending', // retry later
                    'errors' => 'No se pudo contactar a la DGII para verificar estado. Reintentando más tarde.'
                ];
            }

            $data = $response->json();
            $estado = strtolower($data['estado'] ?? 'proceso');

            if ($estado === 'aceptado') {
                return [
                    'status' => 'accepted',
                    'errors' => null
                ];
            }

            if ($estado === 'rechazado') {
                $mensajes = $data['mensajes'] ?? [];
                $errorStr = is_array($mensajes) ? implode(' | ', $mensajes) : (string)$mensajes;
                return [
                    'status' => 'rejected',
                    'errors' => $errorStr ?: 'Rechazado por inconsistencia en validación de reglas de negocio.'
                ];
            }

            return [
                'status' => 'pending',
                'errors' => null
            ];

        } catch (Exception $e) {
            Log::error("[DgiiApiService] Excepción al consultar estado de track {$trackId}: " . $e->getMessage());
            return [
                'status' => 'pending',
                'errors' => $e->getMessage()
            ];
        }
    }
}

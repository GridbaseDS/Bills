<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiKey;

class AuthenticateApiKey
{
    /**
     * Authenticate an incoming request using a Bearer API key.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'error' => 'API key requerida.',
                'message' => 'Incluye tu API key en el header: Authorization: Bearer gb_xxx...',
            ], 401);
        }

        // Validate prefix
        if (!str_starts_with($token, 'gb_')) {
            return response()->json([
                'success' => false,
                'error' => 'Formato de API key inválido.',
                'message' => 'Las API keys de Gridbase Bills comienzan con "gb_".',
            ], 401);
        }

        $apiKey = ApiKey::findByToken($token);

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key inválida.',
            ], 401);
        }

        if (!$apiKey->is_active) {
            return response()->json([
                'success' => false,
                'error' => 'Esta API key ha sido revocada.',
            ], 401);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'error' => 'Esta API key ha expirado.',
                'expired_at' => $apiKey->expires_at->toIso8601String(),
            ], 401);
        }

        // Attach the API key to the request for downstream middleware/controllers
        $request->attributes->set('api_key', $apiKey);

        // Record usage (non-blocking, won't fail the request)
        try {
            $apiKey->recordUsage();
        } catch (\Exception $e) {
            // Silently ignore usage tracking errors
        }

        return $next($request);
    }
}

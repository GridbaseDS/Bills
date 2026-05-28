<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApiKey
{
    /**
     * Rate-limit requests based on the API key's configured rate_limit.
     * Uses Laravel's cache to track request counts per minute.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->attributes->get('api_key');

        if (!$apiKey) {
            return $next($request);
        }

        $maxAttempts = $apiKey->rate_limit ?? 60;
        $cacheKey = 'api_throttle:' . $apiKey->id;
        $decayMinutes = 1;

        // Get current attempt count
        $attempts = (int) Cache::get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            $retryAfter = Cache::get($cacheKey . ':timer', 60);

            return response()->json([
                'success' => false,
                'error' => 'Límite de requests excedido.',
                'message' => "Máximo {$maxAttempts} requests por minuto. Intenta de nuevo en {$retryAfter} segundos.",
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter,
            ]);
        }

        // Increment counter
        if ($attempts === 0) {
            Cache::put($cacheKey, 1, now()->addMinutes($decayMinutes));
            Cache::put($cacheKey . ':timer', 60, now()->addMinutes($decayMinutes));
        } else {
            Cache::increment($cacheKey);
        }

        $response = $next($request);

        // Add rate limit headers to response
        $remaining = max(0, $maxAttempts - $attempts - 1);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
        ]);
    }
}

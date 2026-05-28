<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiLog;

class LogApiRequests
{
    /**
     * Handle an incoming request and log request/response details.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000);

        try {
            // Retrieve the API key injected by AuthenticateApiKey middleware
            $apiKey = $request->attributes->get('api_key');

            // Get raw request payload
            $requestBody = $request->getContent();
            if (empty($requestBody) && !empty($request->all())) {
                $requestBody = json_encode($request->except(['password']));
            }

            // Get response content
            $responseBody = $response->getContent();

            // Truncate payloads at 15KB to avoid database bloat
            $requestBodyStr = mb_strimwidth($requestBody, 0, 15000, '... [Truncated]');
            $responseBodyStr = mb_strimwidth($responseBody, 0, 15000, '... [Truncated]');

            ApiLog::create([
                'api_key_id' => $apiKey ? $apiKey->id : null,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip_address' => $request->ip(),
                'request_body' => $requestBodyStr,
                'response_status' => $response->getStatusCode(),
                'response_body' => $responseBodyStr,
                'duration_ms' => $durationMs,
            ]);
        } catch (\Exception $e) {
            // Fail silently to ensure API operations are never blocked by logging errors
            \Illuminate\Support\Facades\Log::error("API Logger Error: " . $e->getMessage());
        }

        return $response;
    }
}

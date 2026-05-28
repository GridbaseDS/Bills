<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiPermission
{
    /**
     * Check that the authenticated API key has the required permission.
     *
     * Usage: ->middleware('api.permission:invoices.create')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $apiKey = $request->attributes->get('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'No autenticado.',
            ], 401);
        }

        if (!$apiKey->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'error' => 'Permiso insuficiente.',
                'message' => "Esta API key no tiene el permiso: {$permission}",
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}

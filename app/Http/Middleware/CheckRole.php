<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }

        // If no roles specified or user has one of the allowed roles
        if (empty($roles) || in_array($user->role, $roles)) {
            return $next($request);
        }

        return response()->json([
            'error' => 'No autorizado.',
            'message' => 'No tienes permisos para acceder a este recurso.'
        ], 403);
    }
}

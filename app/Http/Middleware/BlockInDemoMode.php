<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockInDemoMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.demo_mode')) {
            return response()->json([
                'success' => false,
                'error' => 'Los módulos de Pruebas DGII y Auditoría DGII están deshabilitados en el entorno de demostración.'
            ], 403);
        }

        return $next($request);
    }
}

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'api.log' => \App\Http\Middleware\LogApiRequests::class,
            'api.key' => \App\Http\Middleware\AuthenticateApiKey::class,
            'api.permission' => \App\Http\Middleware\CheckApiPermission::class,
            'api.throttle' => \App\Http\Middleware\ThrottleApiKey::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'fe/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

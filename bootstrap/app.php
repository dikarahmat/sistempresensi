<?php

use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\RoleKesiswaanMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Daftarkan alias role middleware
        $middleware->alias([
            'role' => EnsureUserRole::class,
            'role.kesiswaan' => RoleKesiswaanMiddleware::class,
        ]);

        // Security headers global
        $middleware->append(SecurityHeaders::class);

        // Railway ada di belakang reverse proxy, percayai header X-Forwarded-*
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
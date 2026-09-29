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

        // Trust reverse proxy yang diketahui (Railway, Heroku, dll)
        // Jangan gunakan '*' karena tidak aman - hanya proxy yang dipercaya
        $middleware->trustProxies(at: [
            // Railway
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
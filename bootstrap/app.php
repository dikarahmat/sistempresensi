<?php

use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

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
        ]);

        // Security headers global
        $middleware->append(SecurityHeaders::class);

        // Percayai header X-Forwarded-* dari reverse proxy
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Pesan rate limit (HTTP 429) untuk halaman login, berbahasa Indonesia.
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if ($request->is('login', 'login/*') && ! $request->expectsJson()) {
                return redirect()
                    ->route('login')
                    ->withInput($request->only('login', 'remember'))
                    ->withErrors([
                        'login' => 'Terlalu banyak percobaan login. Coba lagi dalam beberapa saat.',
                    ]);
            }

            return null;
        });
    })
    ->create()
    ->usePublicPath(
        is_dir(dirname(__DIR__, 2).'/public_html')
            ? dirname(__DIR__, 2).'/public_html'
            : dirname(__DIR__).'/public'
    );
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
        // Daftarkan alias role middleware (hanya ADMIN & GURU)
        $middleware->alias([
            'role' => EnsureUserRole::class,
        ]);

        // Security headers global
        $middleware->append(SecurityHeaders::class);

        // Railway ada di belakang reverse proxy, percayai header X-Forwarded-*
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Pesan rate limit (HTTP 429) untuk halaman login.
        //
        // Secara default Laravel membalas dengan pesan berbahasa Inggris
        // ("Too Many Attempts.") yang membingungkan pengguna sekolah. Kita
        // ganti dengan pesan Bahasa Indonesia yang jelas.
        //
        // Catatan keamanan: pesan ini SENGAJA tidak menyebutkan apakah
        // username-nya terdaftar atau tidak, sehingga tidak membocorkan
        // informasi soal akun yang ada.
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if ($request->is('login', 'login/*') && ! $request->expectsJson()) {
                // Selalu kembali ke halaman login supaya pesan throttle benar-benar
                // terlihat, bukan ke halaman sebelumya yang mungkin sudah tidak relevan.
                return redirect()
                    ->route('login')
                    ->withInput($request->only('login', 'remember'))
                    ->withErrors([
                        'login' => 'Terlalu banyak percobaan login. Coba lagi dalam beberapa saat.',
                    ]);
            }

            return null;
        });
    })->create();
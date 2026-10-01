<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * Memastikan pengguna terautentikasi dan memiliki role yang sesuai.
     * Sistem hanya mengenal dua role: 'admin' dan 'guru'.
     * Jika role tidak cocok, request dibatalkan dengan HTTP 403 (server-side
     * enforcement, bukan sekadar menyembunyikan tombol di UI).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Pastikan pengguna sudah terautentikasi
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // 2. Periksa apakah role pengguna termasuk dalam daftar role yang diizinkan
        if (!in_array($request->user()->role, $roles, true)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleKesiswaanMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validasi hak akses khusus role 'kesiswaan'.
     * Jika pengguna belum login, arahkan ke halaman login.
     * Jika role bukan 'kesiswaan', trigger abort 403 sesuai spesifikasi teknis keamanan.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if ($request->user()->role !== 'kesiswaan') {
            abort(403, 'Akses Ditolak: Halaman ini dilindungi dan hanya dapat diakses oleh Bagian Kesiswaan (Kemahasiswaan).');
        }

        return $next($request);
    }
}

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
     * Jika role tidak cocok, batalkan request dengan HTTP 403 (Forbidden / Unauthorized).
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

        $user = $request->user();

        // 2. Periksa apakah role pengguna termasuk dalam daftar role yang diizinkan
        if (!in_array($user->role, $roles, true)) {
            // Pengalihan ramah (Graceful Redirection) agar Kesiswaan & Guru tidak mengalami 403 saat melihat data
            if ($request->isMethod('get')) {
                if ($request->is('admin/kehadiran*')) {
                    return $user->role === 'guru' 
                        ? redirect()->route('guru.kehadiran') 
                        : redirect()->route('kesiswaan.kehadiran');
                }
                if ($request->is('admin/rekap*')) {
                    return $user->role === 'guru' 
                        ? redirect()->route('guru.rekap') 
                        : redirect()->route('kesiswaan.rekap.index');
                }
                if ($request->is('admin/students*')) {
                    return $user->role === 'guru' 
                        ? redirect()->route('guru.students') 
                        : redirect()->route('kesiswaan.students.index');
                }
                if ($request->is('admin/classes*') && $user->role === 'kesiswaan') {
                    return redirect()->route('kesiswaan.classes.index');
                }
                if ($request->is('admin/teachers*') && $user->role === 'kesiswaan') {
                    return redirect()->route('kesiswaan.teachers.index');
                }
            }

            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
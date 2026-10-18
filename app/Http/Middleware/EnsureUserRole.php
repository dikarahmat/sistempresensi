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

        // 1b. Bahasa (id / en) sesuai pilihan di pop-up kartu akun.
        //     Dijalankan di middleware yang sudah ada ini karena ia berjalan di
        //     dalam group `web` (session sudah aktif) dan dipakai SEMUA halaman
        //     panel (admin/guru/piket). Logikanya ditempatkan di provider agar
        //     middleware ini tetap fokus pada penjagaan role.
        \App\Providers\AppServiceProvider::setLocale($request);

        // 2. Akun NONAKTIF: sesi yang sedang berjalan ikut DITOLAK di sini.
        //    Status dibaca dari database setiap request, sehingga ketika admin
        //    menonaktifkan sebuah akun lewat halaman Kelola Akun, user tersebut
        //    langsung keluar pada request berikutnya tanpa perlu pencabutan
        //    sesi manual.
        if (!$request->user()->isActive()) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => __('Akun tidak dapat digunakan. Hubungi admin sekolah.'),
            ]);
        }

        // 3. Periksa apakah role pengguna termasuk dalam daftar role yang diizinkan
        if (!in_array($request->user()->role, $roles, true)) {
            abort(403, __('Akses Ditolak: Anda tidak memiliki wewenang untuk mengakses halaman ini.'));
        }

        return $next($request);
    }
}
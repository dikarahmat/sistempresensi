<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Helper Panel Admin & Guru (Single Source of Truth)
|--------------------------------------------------------------------------
| Sistem ini hanya memiliki dua role: ADMIN dan GURU. Halaman guru memakai
| view & komponen yang sama persis dengan admin (satu set view bersama),
| sehingga tautan (route) di dalam view perlu di-resolve otomatis sesuai
| panel pengguna yang sedang login.
*/

if (!function_exists('panel_role')) {
    /**
     * Nama panel yang sedang aktif berdasarkan role pengguna (admin|guru).
     */
    function panel_role(): string
    {
        return (Auth::check() && Auth::user()->role === 'guru') ? 'guru' : 'admin';
    }
}

if (!function_exists('is_admin')) {
    /**
     * True jika pengguna yang sedang login adalah ADMIN.
     */
    function is_admin(): bool
    {
        return Auth::check() && Auth::user()->role === 'admin';
    }
}

if (!function_exists('is_guru')) {
    /**
     * True jika pengguna yang sedang login adalah GURU.
     */
    function is_guru(): bool
    {
        return Auth::check() && Auth::user()->role === 'guru';
    }
}

if (!function_exists('panel_route')) {
    /**
     * Resolusi nama route panel dari satu nama logis (tanpa prefix admin/guru).
     * Contoh: panel_route('students.index') => admin.students.index | guru.students.index
     *
     * Route yang memang khusus admin tidak didaftarkan di panel guru. Untuk itu
     * helper mengembalikan '#' agar tidak pernah menghasilkan tautan ke /admin,
     * sementara tombolnya sendiri selalu disembunyikan lewat is_admin().
     */
    function panel_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $candidate = panel_role() . '.' . $name;

        if (Route::has($candidate)) {
            return route($candidate, $parameters, $absolute);
        }

        if (panel_role() === 'admin' && Route::has($name)) {
            return route($name, $parameters, $absolute);
        }

        return '#';
    }
}

if (!function_exists('panel_is')) {
    /**
     * Cek status menu aktif berdasarkan nama route logis (lihat panel_route()).
     */
    function panel_is(string ...$patterns): bool
    {
        $prefix = panel_role();

        foreach ($patterns as $pattern) {
            if (request()->routeIs($prefix . '.' . $pattern)) {
                return true;
            }
        }

        return false;
    }
}

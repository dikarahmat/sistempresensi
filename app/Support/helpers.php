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

if (!function_exists('is_mobile_request')) {
    /**
     * Deteksi apakah request berasal dari perangkat mobile (HP).
     */
    function is_mobile_request(?\Illuminate\Http\Request $request = null): bool
    {
        $req = $request ?: request();

        if ($req->has('mobile')) {
            return $req->boolean('mobile');
        }
        if ($req->has('is_mobile')) {
            return $req->boolean('is_mobile');
        }

        if ($req->header('Sec-CH-UA-Mobile') === '?1' || $req->header('X-Mobile') === '1' || strtolower((string)$req->header('X-Mobile')) === 'true') {
            return true;
        }

        $userAgent = (string) $req->userAgent();
        if ($userAgent === '') {
            return false;
        }

        return (bool) preg_match('/(android|iphone|ipad|ipod|blackberry|windows phone|iemobile|mobile|tablet|opera mini)/i', $userAgent);
    }
}

if (!function_exists('render_compact_pagination')) {
    /**
     * Komponen Pagination Bersama (Single Source of Truth)
     * Desain: Panah Kiri (‹) di kiri, "Halaman X dari Y" di tengah, Panah Kanan (›) di kanan.
     * Area tap minimal 44x44 px, panah dinonaktifkan di halaman pertama/terakhir.
     * Sembunyi otomatis jika total data <= 50 atau tidak memiliki banyak halaman.
     */
    function render_compact_pagination(mixed $paginator, string $scrollTarget = ''): string
    {
        if (!$paginator) {
            return '';
        }

        // Cek apakah paginator memiliki lebih dari 1 halaman
        if (method_exists($paginator, 'hasPages') && !$paginator->hasPages()) {
            return '';
        }

        if (!method_exists($paginator, 'currentPage')) {
            return '';
        }

        $currentPage = $paginator->currentPage();
        $lastPage = method_exists($paginator, 'lastPage') ? $paginator->lastPage() : 1;

        if ($lastPage <= 1) {
            return '';
        }

        $hash = $scrollTarget ? ('#' . ltrim($scrollTarget, '#')) : '';

        $prevUrl = $paginator->previousPageUrl() ? ($paginator->previousPageUrl() . $hash) : null;
        $nextUrl = $paginator->nextPageUrl() ? ($paginator->nextPageUrl() . $hash) : null;

        $isFirst = method_exists($paginator, 'onFirstPage') ? $paginator->onFirstPage() : ($currentPage <= 1);
        $isLast = method_exists($paginator, 'hasMorePages') ? !$paginator->hasMorePages() : ($currentPage >= $lastPage);

        $html = '<div class="pagination-arrow-container w-100 d-flex justify-content-center align-items-center py-3">';
        $html .= '<div class="pagination-arrow-bar d-flex align-items-center justify-content-between">';

        // Tombol Panah Kiri (‹)
        if ($isFirst || !$prevUrl) {
            $html .= '<span class="pagination-arrow-btn is-disabled" aria-disabled="true" aria-label="Sebelumnya" title="Halaman Pertama">';
            $html .= '<i class="bx bx-chevron-left"></i>';
            $html .= '</span>';
        } else {
            $html .= '<a href="' . e($prevUrl) . '" class="pagination-arrow-btn" aria-label="Sebelumnya" title="Halaman Sebelumnya">';
            $html .= '<i class="bx bx-chevron-left"></i>';
            $html .= '</a>';
        }

        // Teks Tengah: Halaman X dari Y
        $html .= '<div class="pagination-arrow-info">';
        $html .= 'Halaman <span class="fw-bold text-dark">' . $currentPage . '</span> dari <span class="fw-bold text-dark">' . $lastPage . '</span>';
        $html .= '</div>';

        // Tombol Panah Kanan (›)
        if ($isLast || !$nextUrl) {
            $html .= '<span class="pagination-arrow-btn is-disabled" aria-disabled="true" aria-label="Berikutnya" title="Halaman Terakhir">';
            $html .= '<i class="bx bx-chevron-right"></i>';
            $html .= '</span>';
        } else {
            $html .= '<a href="' . e($nextUrl) . '" class="pagination-arrow-btn" aria-label="Berikutnya" title="Halaman Berikutnya">';
            $html .= '<i class="bx bx-chevron-right"></i>';
            $html .= '</a>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}


<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Helper panel bersama (admin & guru) - dijaga require_once agar tetap
        // tersedia walau autoload composer belum di-dump ulang di server.
        require_once app_path('Support/helpers.php');
    }

    /**
     * Bahasa aplikasi per request (id / en).
     *
     * Dipanggil dari middleware yang sudah ada
     * (App\Http\Middleware\EnsureUserRole) supaya tidak perlu file middleware
     * baru. Dijalankan di dalam group `web`, jadi session sudah tersedia.
     *
     * Default tetap Bahasa Indonesia (config app.locale = id): tanpa pilihan
     * tersimpan, seluruh tampilan sama persis seperti sebelumnya.
     */
    public static function setLocale($request): void
    {
        $supported = ['id', 'en'];
        $locale = $request->session()->get('locale');

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.locale', 'id');
        }

        if (! in_array($locale, $supported, true)) {
            $locale = 'id';
        }

        app()->setLocale($locale);

        // Tanggal ikut bahasa aktif: "Minggu, 18 Oktober 2026" (id) menjadi
        // "Sunday, 18 October 2026" (en).
        \Carbon\Carbon::setLocale($locale);
        \Carbon\CarbonImmutable::setLocale($locale);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set locale Indonesia global untuk Carbon (nama hari/bulan Bahasa Indonesia)
        \Carbon\Carbon::setLocale('id');

        \Illuminate\Http\Request::macro('isMobile', function () {
            return is_mobile_request($this);
        });

        // ---------------------------------------------------------------
        // Limiter khusus CETAK REKAP ("Semua Kelas").
        //
        // Cetak Per Siswa + cakupan "Semua Kelas" mengirim SATU request per
        // kelas dari browser. Dengan ~18 kelas, throttle lama 5 request/menit
        // langsung memblokir 13 kelas dengan HTTP 429 "Too Many Attempts".
        // Karena itu rute cetak memakai limiter bernama yang longgar:
        // 120 request per menit per user. Rute lain TIDAK diubah.
        // ---------------------------------------------------------------
        \Illuminate\Support\Facades\RateLimiter::for('rekap-print', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by(
                optional($request->user())->id ?: $request->ip()
            );
        });
    }
}

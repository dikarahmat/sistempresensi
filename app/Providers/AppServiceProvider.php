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

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
    }
}

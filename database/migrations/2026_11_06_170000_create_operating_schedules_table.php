<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Jadwal operasional presensi per hari (Senin-Jumat).
     * Migrasi aman dijalankan ulang: tabel & baris yang sudah ada tidak ditimpa.
     */
    public function up(): void
    {
        if (!Schema::hasTable('operating_schedules')) {
            Schema::create('operating_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('day_of_week')->unique();
                $table->time('check_in_time');
                $table->time('late_limit_time');
                $table->time('check_out_time');
                $table->time('check_out_limit_time');
                $table->timestamps();
            });
        }

        // Nilai awal diambil dari pengaturan lama supaya perilaku hari pertama
        // setelah migrasi sama dengan sebelumnya. Baris yang sudah ada
        // (mis. hasil edit admin) TIDAK ditimpa.
        $settings = Schema::hasTable('settings')
            ? DB::table('settings')->whereIn('key', ['check_in_time', 'late_limit_time', 'check_out_time'])->pluck('value', 'key')
            : collect();

        $checkIn = $settings->get('check_in_time') ?: '06:45';
        $lateLimit = $settings->get('late_limit_time') ?: '07:15';
        $checkOut = $settings->get('check_out_time') ?: '14:00';
        $checkOutLimit = date('H:i', strtotime($checkOut . ' +60 minutes'));

        foreach ([1, 2, 3, 4, 5] as $day) {
            $exists = DB::table('operating_schedules')->where('day_of_week', $day)->exists();
            if (!$exists) {
                DB::table('operating_schedules')->insert([
                    'day_of_week' => $day,
                    'check_in_time' => $checkIn,
                    'late_limit_time' => $lateLimit,
                    'check_out_time' => $checkOut,
                    'check_out_limit_time' => $checkOutLimit,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operating_schedules');
    }
};

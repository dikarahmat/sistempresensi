<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Modifikasi tabel teachers
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'phone_number')) {
                $table->string('phone_number', 20)->nullable()->after('birth_date');
            }
            if (Schema::hasColumn('teachers', 'phone')) {
                $table->string('phone', 20)->nullable()->change();
            }
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('name', 255)->change();
            $table->string('nip', 50)->nullable()->change();
            $table->string('birth_place', 100)->nullable()->change();
            $table->date('birth_date')->nullable()->change();
        });

        // 2. Modifikasi tabel students
        Schema::table('students', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->string('nis', 30)->change();
            $table->string('nisn', 30)->nullable()->change();
            $table->string('birth_place', 100)->nullable()->change();
            $table->date('birth_date')->nullable()->change();
            $table->string('parent_name', 255)->nullable()->change();
            $table->string('parent_phone', 20)->nullable()->change();
            $table->string('photo', 255)->nullable()->change();
            $table->string('qr_token', 255)->nullable()->change();
        });

        // 3. CATATAN PENTING - BLOK RESET DATA SUDAH DIHAPUS (tanggal 2026-10-03)
        //
        // Versi migration ini sebelumnya berisi blok:
        //     DB::table('attendances')->truncate();
        //     DB::table('school_classes')->update(['teacher_id' => null]);
        //     DB::table('students')->truncate();
        //     DB::table('teachers')->truncate();
        //     ALTER TABLE students/teachers AUTO_INCREMENT = 1;
        // yang dijalankan setiap kali migration ini dieksekusi pada environment
        // non-production.
        //
        // Migration bersifat BERJALAN SEKALI per database. Namun jika dikombinasikan
        // dengan `migrate:fresh` / `migrate:refresh` (yang dipanggil RefreshDatabase
        // saat test), blok tersebut ikut ter-eksekusi dan MENGHAPUS SELURUH data
        // siswa, guru, dan presensi di database development. Ini salah satu sumber
        // kehilangan data, jadi blok destruktifnya dihapus permanen.
        //
        // Jika diperlukan membersihkan data, JANGAN lewat migration. Gunakan
        // perintah eksplisit dan sadar: `php artisan db:wipe` / truncate manual,
        // atau tombol "Hapus Semua" di aplikasi (yang sudah dibatasi per tabel).
    }

    public function down(): void
    {
        // Reversible structure if needed
    }
};

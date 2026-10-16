<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status akun login (fitur Kelola Akun, 2026-10-14).
     *
     * Kolom users.status menentukan apakah sebuah akun BOLEH LOGIN:
     *   - 'Aktif'   : akun bisa login normal.
     *   - 'Nonaktif': login ditolak di AuthController, dan sesi yang sedang
     *                 aktif ikut ditolak middleware EnsureUserRole pada
     *                 request berikutnya (middleware membaca status dari DB
     *                 setiap request).
     *
     * Keputusan desain:
     *   - TIDAK ada penghapusan permanen akun di aplikasi Kelola Akun;
     *     menonaktifkan dipakai agar riwayat data (presensi, log) tidak rusak.
     *   - DEFAULT 'Aktif' sehingga SEMUA akun yang sudah ada di database
     *     production tetap bisa login setelah migration ini dijalankan -
     *     tidak ada akun yang tiba-tiba terkunci.
     *   - Tipe varchar (bukan enum), konsisten dengan kolom users.role yang
     *     sudah diubah ke varchar(50) oleh migration
     *     2026_10_01_000001_remove_legacy_role_and_accounts.
     *
     * Sifat migration:
     *   - idempotent: dicek Schema::hasColumn() lebih dulu, aman dijalankan
     *     berulang / pada database yang kolomnya sudah ada.
     *   - kompatibel MySQL (production) dan SQLite (testing).
     *   - tidak menyentuh tabel lain.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'status')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 10)->default('Aktif')->after('role');
        });
    }

    /**
     * Rollback: kolom status dihapus. Akun yang sudah bernilai 'Nonaktif'
     * kembali dianggap aktif karena kolomnya tidak ada lagi.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'status')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom password_terenkripsi ke tabel users.
 *
 * Kolom ini menyimpan salinan password (plaintext yang diterima admin)
 * yang sudah di-crypt dengan Crypt::encryptString(), sehingga bisa
 * ditampilkan kembali di modal Edit akun (Bagian 4).
 *
 * Design:
 *   - Kolom password tetap menyimpan HASH (bcrypt) untuk verifikasi login.
 *   - Kolom password_terenkripsi menyimpan ENKRIPSI (reversibel) untuk
 *     ditampilkan di UI admin saat modal Edit dibuka.
 *   - Kolom ini nullable: akun lama (sebelum migration ini) yang belum
 *     pernah disentuh lewat Kelola Akun tidak akan memiliki nilai di sini.
 *   - Kolom ini tidak pernah diisi di migration — nilainya diset di:
 *       1. DatabaseSeeder::seedAccounts() (untuk akun admin dev awal)
 *       2. UserAccountController::store() & update() (untuk akun baru/edit)
 *
 * Keamanan:
 *   - Kolom ini ditambahkan ke $hidden di model User agar tidak pernah
 *     bocor melalui API/JSON response secara tidak sengaja.
 *   - Hanya endpoint GET /admin/kelola-akun/{user}/password (role:admin)
 *     yang bisa mendekripsi dan mengembalikan nilainya sebagai JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'password_terenkripsi')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->text('password_terenkripsi')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'password_terenkripsi')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_terenkripsi');
        });
    }
};

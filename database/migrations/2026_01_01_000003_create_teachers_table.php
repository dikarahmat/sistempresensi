<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * CATATAN PENTING (2026-10-03) - PERLINDUNGAN TABEL `users`.
     *
     * Kolom `teachers.user_id` TIDAK BOLEH memakai CASCADE.
     *
     *MASALAH: `cascadeOnDelete()` membuat menghapus satu baris di tabel
     * `users` ikut menghapus seluruh profil guru yang terhubung. Password
     * akun pun ikut hilang, sehingga akun guru tidak bisa login lagi -
     * persis gejala "Username atau kata sandi yang Anda masukkan salah".
     *
     * PERBAIKAN: FK memakai `nullOnDelete()` dan kolom `user_id` nullable,
     * sehingga menghapus akun guru hanya mengosongkan kolom user_id - profil
     * guru dan tabel `users` tidak pernah ikut terhapus.
     *
     * Penghapusan akun admin dicegah terpisah di App\Models\User (event model
     * `deleting`), karena itu berlaku untuk semua jalur kode.
     *
     * Kolom nullable ini juga sudah dijamin migration
     * 2026_09_11_071329_make_user_id_nullable_in_teachers_table.php, sehingga
     * kedua migration ini saling melengkapi dan tidak bertentangan.
     */
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nip', 30)->unique()->nullable();
            $table->string('name', 100);
            $table->enum('gender', ['Laki-laki', 'Perempuan'])->default('Laki-laki');
            $table->string('birth_place', 50)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
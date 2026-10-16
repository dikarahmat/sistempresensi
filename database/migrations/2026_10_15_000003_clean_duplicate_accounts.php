<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus akun duplikat sehingga hanya tersisa SATU akun per role.
 *
 * Akun yang dipertahankan (prioritas):
 *   - admin@presensi.com        (Admin)
 *   - guru@presensi.com         (Guru)
 *   - dikarahmat998@gmail.com   (Guru Piket)
 *
 * Semua akun lain (termasuk js@uji.test dan akun @guru.smppresensipgri.sch.id)
 * akan dihapus secara permanen.
 *
 * Relasi users.id:
 *   - teachers.user_id -> FK constrained()->nullOnDelete() — set NULL sebelum delete
 *   - sessions.user_id -> hanya index, bukan FK — delete session secara manual
 *
 * NOTE: JANGAN jalankan migration ini di environment production tanpa
 *   memastikan backup database. Migration ini permanen menghapus data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'email')) {
            return;
        }

        $keptEmails = ['admin@presensi.com', 'guru@presensi.com', 'dikarahmat998@gmail.com'];

        DB::transaction(function () use ($keptEmails) {
            // 1. Identifikasi ID akun yang akan dihapus (semua kecuali yang dipertahankan)
            $deleteIds = DB::table('users')
                ->whereNotIn('email', $keptEmails)
                ->pluck('id');

            if ($deleteIds->isEmpty()) {
                return;
            }

            $ids = $deleteIds->all();

            // 2. Lepaskan relasi teachers.user_id -> NULL (FK nullOnDelete)
            if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'user_id')) {
                DB::table('teachers')
                    ->whereIn('user_id', $ids)
                    ->update(['user_id' => null]);
            }

            // 3. Hapus sessions yang terkait (sessions.user_id hanyalah index)
            if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                DB::table('sessions')
                    ->whereIn('user_id', $ids)
                    ->delete();
            }

            // 4. Hapus akun user secara permanen
            DB::table('users')
                ->whereIn('id', $ids)
                ->delete();
        });
    }

    /**
     * Tidak ada rollback: akun yang sudah dihapus tidak bisa direstore otomatis.
     */
    public function down(): void
    {
        //
    }
};

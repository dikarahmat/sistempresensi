<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus akun User lama yang dibuat otomatis dari NIP guru.
 *
 * Latar belakang:
 *   Pada versi sebelumnya, AuthController::resolveTeacherUser() dan command
 *   teachers:sync-accounts membuat akun User dengan email pola
 *   '{nip}@guru.smppresensipgri.sch.id' dan password = hash dari NIP.
 *   Akun-akun ini sudah tidak lagi dipakai kukuh karena login NIP dihapus.
 *   Pada Kelola Akun (Bagian 3), admin bisa membuat akun guru baru secara manual
 *   dengan email & password yang ditentukan — sehingga akun NIP lama yang
 *   duplikat/redundant sebaiknya dihapus.
 *
 * Kriteria akun yang AKAN DIHAPUS:
 *   - email LIKE '%@guru.smppresensipgri.sch.id'  (akun hasil NIP lama)
 *   - role = 'guru'  (hanya guru, bukan admin/piket)
 *   - username IS NULL  (akun hasil seeder/legacy yang punya username dilindungi)
 *   - user_id tidak menjadi wali kelas di school_classes (relasi teachers)
 *
 * Akun yang TIDAK AKAN DIHAPUS:
 *   - Semua role 'admin'
 *   - Semua role 'piket'
 *   - Semua akun yang punya username non-null (legacy admin/guru via seeder)
 *   - Akun yang menjadi wali kelas (relasi teachers -> school_classes)
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

        DB::transaction(function () {
            // 1. Identifikasi user_id semua guru yang menjadi wali kelas.
            //    Akun wali kelas TIDAK boleh dihapus karena menghapusnya akan
            //    merusak tampilan kelas di school_classes.
            $waliKelasIds = collect();
            if (Schema::hasTable('school_classes') && Schema::hasColumn('school_classes', 'teacher_id')) {
                $waliKelasIds = DB::table('school_classes')
                    ->whereNotNull('teacher_id')
                    ->join('teachers', 'teachers.id', '=', 'school_classes.teacher_id')
                    ->whereNotNull('teachers.user_id')
                    ->pluck('teachers.user_id');
            }

            // 2. Kriteria ID akun yang akan dihapus:
            //    - email pola NIP lama: '%@guru.smppresensipgri.sch.id'
            //    - role = 'guru'
            //    - username IS NULL (akun otomatis, tidak legacy)
            //    - TIDAK menjadi wali kelas
            $deleteIds = DB::table('users')
                ->where('email', 'like', '%@guru.smppresensipgri.sch.id')
                ->where('role', 'guru')
                ->whereNull('username')
                ->when($waliKelasIds->isNotEmpty(), function ($q) use ($waliKelasIds) {
                    $q->whereNotIn('id', $waliKelasIds->all());
                })
                ->pluck('id');

            if ($deleteIds->isEmpty()) {
                return;
            }

            $ids = $deleteIds->all();

            // 3. Lepaskan relasi teachers.user_id -> NULL (FK nullOnDelete).
            if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'user_id')) {
                DB::table('teachers')
                    ->whereIn('user_id', $ids)
                    ->update(['user_id' => null]);
            }

            // 4. Hapus sessions yang terkait (sessions.user_id hanyalah index).
            if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                DB::table('sessions')
                    ->whereIn('user_id', $ids)
                    ->delete();
            }

            // 5. Hapus akun user secara permanen.
            DB::table('users')
                ->whereIn('id', $ids)
                ->delete();
        });
    }

    /**
     * Tidak ada rollback: akun NIP lama yang sudah dihapus tidak bisa
     * direstore otomatis (passwordnya adalah hash dari NIP yang tidak
     * tersimpan lagi di mana pun).
     * Backup database sebelum migrasi tersedia sebagai dump SQL di luar project.
     */
    public function down(): void
    {
        //
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PERBAIKAN ANTI DOUBLE ABSEN (Audit Fase 6) - 2026-10-16.
 *
 * Unique index lama: (student_id, date, academic_year_id). Karena kolom
 * academic_year_id dibuat NULLABLE (lihat 2026_09_28_000001), MySQL maupun
 * SQLite menganggap NULL berbeda satu sama lain, sehingga saat TIDAK ada
 * tahun ajaran aktif, dua baris presensi untuk siswa & tanggal yang sama
 * tetap bisa masuk. Artinya proteksi anti-double-absen lapis DATABASE bocor.
 *
 * Migrasi ini:
 *   1. membersihkan duplikat lama (pertahankan baris id terkecil = catatan
 *      pertama) supaya penambahan unique tidak gagal;
 *   2. menambah unique (student_id, date) yang tidak bergantung pada kolom
 *      nullable, sehingga proteksi database selalu aktif di semua role
 *      (admin/guru/piket) dan semua jalur input.
 *
 * Idempotent: aman dijalankan berulang di database yang sudah bersih.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Bersihkan duplikat: sisakan satu baris per (student_id, date).
        $duplikat = DB::table('attendances')
            ->select('student_id', 'date', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('student_id', 'date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplikat as $dup) {
            DB::table('attendances')
                ->where('student_id', $dup->student_id)
                ->where('date', $dup->date)
                ->where('id', '<>', $dup->keep_id)
                ->delete();
        }

        // 2. Satu catatan presensi per siswa per hari (lintas role & jalur).
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasIndex('attendances', 'attendances_student_id_date_unique')) {
                $table->unique(['student_id', 'date'], 'attendances_student_id_date_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasIndex('attendances', 'attendances_student_id_date_unique')) {
                $table->dropUnique('attendances_student_id_date_unique');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identitas siswa resmi hanya NISN (Nomor Induk Siswa Nasional).
     *
     * Kolom students.nis dihapus TOTAL:
     *   0. VALIDASI DULU (sebelum ada perubahan sama sekali): pastikan
     *      students.nisn terisi dan unik untuk SEMUA baris. Kalau ada baris
     *      dengan nisn NULL/kosong atau nisn duplikat, migration DIJEDA dengan
     *      pesan jelas — data tidak boleh hilang diam-diam.
     *   1. Semua index / unique index yang memuat kolom `nis` dilepas lebih dulu
     *      (MySQL & SQLite sama-sama menolak drop kolom bila masih terindeks).
     *   2. Baris kolom `nis` lalu dihapus.
     *   3. students.nisn dipastikan punya unique index (dicek lewat hasIndex,
     *      ditambahkan bila belum ada) karena NISN kini menjadi identitas tunggal.
     *
     * Migration ini idempotent: aman dijalankan ulang, dan aman baik di MySQL
     * (Railway/production) maupun SQLite (testing/lokal).
     */
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        // 0. Gerbang data: NISN wajib terisi & unik untuk semua baris sebelum
        //    kolom NIS dibuang. Berhenti di sini kalau datanya belum bersih.
        $this->assertNisnSiapJadiIdentitas();

        // 1. Lepas semua index yang kolomnya memuat `nis` (index biasa maupun unique).
        if (Schema::hasColumn('students', 'nis')) {
            foreach (Schema::getIndexes('students') as $index) {
                if (($index['primary'] ?? false) === true) {
                    continue;
                }

                $columns = array_map('strtolower', (array) ($index['columns'] ?? []));

                if (in_array('nis', $columns, true)) {
                    Schema::table('students', function (Blueprint $table) use ($index) {
                        $table->dropIndex($index['name']);
                    });
                }
            }

            // 2. Drop kolom nis.
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('nis');
            });
        }

        // 3. Pastikan nisn punya unique index (identitas tunggal siswa).
        if (Schema::hasColumn('students', 'nisn') && !Schema::hasIndex('students', 'students_nisn_unique')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unique('nisn', 'students_nisn_unique');
            });
        }
    }

    /**
     * Pastikan students.nisn benar-benar siap jadi identitas tunggal siswa.
     *
     * Gagal (throw) kalau:
     *  - kolom nisn tidak ada sama sekali;
     *  - ada baris dengan nisn NULL atau string kosong;
     *  - ada nisn yang sama muncul lebih dari sekali (unique index akan gagal).
     *
     * Pesan exception sengaja memuat daftar NISN bermasalah (maks. 10) supaya
     * data bisa langsung diperbaiki tanpa perlu menebak.
     */
    private function assertNisnSiapJadiIdentitas(): void
    {
        if (!Schema::hasColumn('students', 'nisn')) {
            throw new \RuntimeException(
                'Migration dihentikan: tabel students tidak punya kolom nisn. '
                . 'NISN harus ada sebelum kolom nis dapat dihapus.'
            );
        }

        // Kosong = NULL atau hanya spasi.
        $kosong = DB::table('students')
            ->select('id', 'nisn')
            ->whereNull('nisn')
            ->orWhere('nisn', '')
            ->orWhere('nisn', ' ')
            ->limit(10)
            ->get();

        if ($kosong->isNotEmpty()) {
            $daftar = $kosong->map(fn ($r) => "id={$r->id}, nisn=" . var_export($r->nisn, true))->implode('; ');

            throw new \RuntimeException(
                'Migration dihentikan: ada ' . $kosong->count() . '+ baris siswa dengan NISN NULL/kosong. '
                . 'NISN wajib diisi untuk semua siswa sebelum kolom nis dihapus. '
                . 'Contoh baris bermasalah -> ' . $daftar
            );
        }

        $duplikat = DB::table('students')
            ->select('nisn', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('nisn')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($duplikat->isNotEmpty()) {
            $daftar = $duplikat->map(fn ($r) => "nisn={$r->nisn} ({$r->jumlah} baris)")->implode('; ');

            throw new \RuntimeException(
                'Migration dihentikan: ada NISN duplikat pada tabel students. '
                . 'NISN harus unik untuk semua siswa sebelum kolom nis dihapus. '
                . 'Contoh duplikat -> ' . $daftar
            );
        }
    }

    /**
     * Rollback: kolom nis dikembalikan sebagai kolom nullable (tanpa nilai,
     * tidak ada data NIS lama yang dipulihkan karena nilainya sudah dibuang).
     */
    public function down(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        if (!Schema::hasColumn('students', 'nis')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('nis', 30)->nullable();
            });
        }
    }
};
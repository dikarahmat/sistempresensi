<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PEMBERSIHAN KOLOM MATI (satu-satu untuk semua drop kolom).
     *
     * Setelah identitas siswa disederhanakan (cuma NISN, nama, kelas, jenis
     * kelamin) dan NIS dihapus, beberapa kolom di tabel `teachers` turns out
     * tidak pernah dibaca maupun ditulis fitur mana pun - tidak ada di form,
     * bukan kolom import, tidak tampil di view, tidak dipakai controller, dan
     * tidak ada di seeder/factory/test:
     *
     *   teachers.nuptk      -> hanya ada di Teacher::$fillable
     *   teachers.religion   -> hanya ada di Teacher::$fillable
     *   teachers.subject    -> hanya ada di Teacher::$fillable
     *   teachers.notes      -> hanya ada di Teacher::$fillable
     *
     * Kolom `address` sengaja TIDAK ikut di-drop walaupun import menulisnya:
     * kolom itu masih menyimpan data yang sudah pernah diimpor user, jadi
     * penghapusannya diputuskan terpisah oleh pemilik sistem (lihat laporan).
     *
     * Sifat migration:
     *  - FAIL-FAST: sebelum menghapus apa pun, data siswa diverifikasi dulu
     *    (nisn tidak boleh NULL/kosong dan tidak boleh duplikat). Kalau ada
     *    masalah, migration berhenti dengan pesan jelas dan TIDAK ada satu pun
     *    perubahan yang dilakukan ke database.
     *  - idempotent: tiap kolom dicek hasColumn() lebih dulu.
     *  - kompatibel MySQL (Railway/production) dan SQLite (testing/lokal).
     */
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            $this->assertNisnTetapBersih();
        }

        foreach ($this->kolomMati() as $tabel => $kolom) {
            if (!Schema::hasTable($tabel)) {
                continue;
            }

            foreach ($kolom as $nama) {
                if (!Schema::hasColumn($tabel, $nama)) {
                    continue;
                }

                // 1. Lepas index yang memuat kolom ini (MySQL menolak drop kolom
                //    yang masih bagian index / unique index).
                foreach (Schema::getIndexes($tabel) as $index) {
                    if (($index['primary'] ?? false) === true) {
                        continue;
                    }

                    $columns = array_map('strtolower', (array) ($index['columns'] ?? []));

                    if (in_array($nama, $columns, true)) {
                        Schema::table($tabel, function (Blueprint $table) use ($index) {
                            $table->dropIndex($index['name']);
                        });
                    }
                }

                // 2. Lepas foreign key yang menempel pada kolom ini (kalau ada).
                foreach (Schema::getForeignKeys($tabel) as $foreignKey) {
                    $columns = array_map('strtolower', (array) ($foreignKey['columns'] ?? []));

                    if (in_array($nama, $columns, true)) {
                        Schema::table($tabel, function (Blueprint $table) use ($foreignKey) {
                            $table->dropForeign($foreignKey['name']);
                        });
                    }
                }

                // 3. Drop kolomnya.
                Schema::table($tabel, function (Blueprint $table) use ($nama) {
                    $table->dropColumn($nama);
                });
            }
        }
    }

    /**
     * Gerbang data: students.nisn wajib terisi & unik untuk semua baris.
     *
     * Kalau ternyata ada baris bermasalah, migration berhenti SEBELUM ada
     * perubahan apa pun supaya data tidak hilang diam-diam.
     */
    private function assertNisnTetapBersih(): void
    {
        if (!Schema::hasColumn('students', 'nisn')) {
            throw new \RuntimeException(
                'Migration dihentikan: tabel students tidak punya kolom nisn.'
            );
        }

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
                . 'NISN wajib diisi untuk semua siswa. Contoh -> ' . $daftar
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
                . 'NISN harus unik untuk semua siswa. Contoh -> ' . $daftar
            );
        }
    }

    /**
     * Rollback: kolom-kolom mati dikembalikan sebagai NULLABLE.
     *
     * PENTING: nilai data lama TIDAK bisa dipulihkan. Kolom kembali ada tapi
     * isinya NULL, karena data yang di-drop di up() sudah dibuang dari database.
     * Satu-satunya sumber data lama hanya dump/backup sebelum migration ini
     * dijalankan.
     */
    public function down(): void
    {
        if (!Schema::hasTable('teachers')) {
            return;
        }

        foreach ($this->kolomMati()['teachers'] ?? [] as $nama) {
            if (Schema::hasColumn('teachers', $nama)) {
                continue;
            }

            Schema::table('teachers', function (Blueprint $table) use ($nama) {
                match ($nama) {
                    'nuptk' => $table->string('nuptk', 30)->nullable(),
                    'religion' => $table->string('religion', 30)->nullable(),
                    'subject' => $table->string('subject', 100)->nullable(),
                    'notes' => $table->text('notes')->nullable(),
                    default => $table->string($nama)->nullable(),
                };
            });
        }
    }

    /**
     * Peta tabel => kolom yang dibuang di up().
     *
     * @return array<string, list<string>>
     */
    private function kolomMati(): array
    {
        return [
            'teachers' => ['nuptk', 'religion', 'subject', 'notes'],
        ];
    }
};

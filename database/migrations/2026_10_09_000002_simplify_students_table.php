<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penyederhanaan data siswa.
     *
     * Data siswa yang dipakai aplikasi sekarang hanya 4 kolom:
     *   nisn, name, gender, school_class_id
     * (plus kolom internal: id, status, photo, qr_token, timestamp, deleted_at).
     *
     * Kolom data KTP/GTK di bawah ini dihapus PERMANEN dari tabel students:
     *   birth_place, birth_date, address,
     *   parent_name, parent_phone, parent_occupation,
     *   phone, religion, email, notes
     *
     * Urutan drop per kolom (penting, MySQL & SQLite sama-sama ketat):
     *   1. index / unique index yang memuat kolom tersebut dilepas;
     *   2. foreign key yang menempel pada kolom tersebut dilepas;
     *   3. baru kolomnya di-drop.
     *
     * Sifat migration:
     *  - idempotent: setiap kolom dicek hasColumn() lebih dulu, jadi aman
     *    dijalankan berulang kali / pada DB yang kolomnya sudah hilang;
     *  - kompatibel MySQL (Railway/production) dan SQLite (testing/lokal);
     *  - tidak menyentuh tabel lain. Kolom `notes` yang juga ada di tabel
     *    `attendances` TIDAK terpengaruh karena drop hanya di tabel `students`.
     */
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        foreach ($this->kolomDihapus() as $kolom) {
            if (!Schema::hasColumn('students', $kolom)) {
                continue;
            }

            // 1. Lepas index yang memuat kolom ini (MySQL menolak drop kolom
            //    yang masih jadi bagian index / unique index).
            foreach (Schema::getIndexes('students') as $index) {
                if (($index['primary'] ?? false) === true) {
                    continue;
                }

                $columns = array_map('strtolower', (array) ($index['columns'] ?? []));

                if (in_array($kolom, $columns, true)) {
                    Schema::table('students', function (Blueprint $table) use ($index) {
                        $table->dropIndex($index['name']);
                    });
                }
            }

            // 2. Lepas foreign key yang menempel pada kolom ini (kalau ada).
            foreach (Schema::getForeignKeys('students') as $foreignKey) {
                $columns = array_map('strtolower', (array) ($foreignKey['columns'] ?? []));

                if (in_array($kolom, $columns, true)) {
                    Schema::table('students', function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey['name']);
                    });
                }
            }

            // 3. Drop kolomnya.
            Schema::table('students', function (Blueprint $table) use ($kolom) {
                $table->dropColumn($kolom);
            });
        }
    }

    /**
     * Rollback: kolom-kolom tersebut dikembalikan sebagai NULLABLE.
     *
     * PENTING: nilai data lama TIDAK bisa dipulihkan. Kolom kembali ada tapi
     * semua isinya NULL, karena data yang di-drop di up() sudah dibuang dari
     * database dan tidak disimpan di tempat lain. Kalau data KTP/GTK masih
     * dibutuhkan, satu-satunya sumbernya dump/backup sebelum migration ini
     * dijalankan.
     */
    public function down(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        foreach ($this->definisiKolomNullable() as $kolom => $definisi) {
            if (Schema::hasColumn('students', $kolom)) {
                continue;
            }

            Schema::table('students', function (Blueprint $table) use ($kolom, $definisi) {
                $definisi($table);
            });
        }
    }

    /**
     * Daftar kolom yang dihapus di up().
     *
     * @return list<string>
     */
    private function kolomDihapus(): array
    {
        return [
            'birth_place',
            'birth_date',
            'address',
            'parent_name',
            'parent_phone',
            'parent_occupation',
            'phone',
            'religion',
            'email',
            'notes',
        ];
    }

    /**
     * Definisi kolom untuk down(): semuanya nullable, dengan panjang yang sama
     * seperti skema students sebelum kolom-kolom ini dihapus.
     *
     * @return array<string, callable(Blueprint): void>
     */
    private function definisiKolomNullable(): array
    {
        return [
            'birth_place' => function (Blueprint $table): void {
                $table->string('birth_place', 100)->nullable();
            },
            'birth_date' => function (Blueprint $table): void {
                $table->date('birth_date')->nullable();
            },
            'address' => function (Blueprint $table): void {
                $table->text('address')->nullable();
            },
            'parent_name' => function (Blueprint $table): void {
                $table->string('parent_name', 255)->nullable();
            },
            'parent_phone' => function (Blueprint $table): void {
                $table->string('parent_phone', 20)->nullable();
            },
            'parent_occupation' => function (Blueprint $table): void {
                $table->string('parent_occupation', 100)->nullable();
            },
            'phone' => function (Blueprint $table): void {
                $table->string('phone', 20)->nullable();
            },
            'religion' => function (Blueprint $table): void {
                $table->string('religion', 30)->nullable();
            },
            'email' => function (Blueprint $table): void {
                $table->string('email', 255)->nullable();
            },
            'notes' => function (Blueprint $table): void {
                $table->text('notes')->nullable();
            },
        ];
    }
};

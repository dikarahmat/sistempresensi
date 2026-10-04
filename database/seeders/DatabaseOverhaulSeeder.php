<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * SEEDER INI SUDAH DINONAKTIFKAN (2026-10-03).
 *
 * Seeder ini sebelumnya melakukan TRUNCATE MASSAL:
 *     DB::table('attendances')->truncate();
 *     DB::table('school_classes')->update(['teacher_id' => null]);
 *     DB::table('students')->truncate();
 *     DB::table('teachers')->truncate();
 *     ALTER TABLE students/teachers AUTO_INCREMENT = 1;
 *
 * Membiarkan perintah destruktif seperti ini berada di file seeder sangat
 * berbahaya: `php artisan db:seed` adalah perintah yang biasa dijalankan orang,
 * tetapi efeknya menghapus SELURUH data siswa, guru, dan presensi tanpa pernah
 * ditanyakan lebih dulu.
 *
 * Karena itu seluruh truncate/TRUNCATE sudah dihapus total. Seeder ini kini
 * tidak menyentuh tabel apa pun, sehingga aman dijalankan berulang (idempotent)
 * dan tidak mungkin mereset data yang sudah ada.
 *
 * Untuk membersihkan data, gunakan hanya tombol "Hapus Semua" di aplikasi
 * (sudah dibatasi per tabel) atau perintah manual yang memang disengaja.
 */
class DatabaseOverhaulSeeder extends Seeder
{
    public function run(): void
    {
        // Sengaja kosong - lihat penjelasan di docblock di atas.
    }
}

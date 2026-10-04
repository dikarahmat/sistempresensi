<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Wrapper yang meneruskan ke DatabaseSeeder (sumber kebenaran tunggal).
 *
 * SEBELUMNYA seeder ini memakai kredensial BERBEDA dari DatabaseSeeder
 * (akun admin@smppresensipgri.sch.id dengan admin123) dan juga membuat data
 * guru + kelas contoh. Itu menimbulkan dua masalah:
 *   1. ada dua akun admin dengan password berbeda -> membingungkan;
 *   2. seeder ini bisa menimpa password akun yang sudah ada.
 *
 * Sekarang seeding akun sepenuhnya ditentukan DatabaseSeeder, sehingga
 * `db:seed --class=UserSeeder` menghasilkan kredensial yang sama persis dan
 * tidak pernah menimpa password dengan nilai lain.
 */
class UserSeeder extends Seeder
{
    /**
     * Jalankan seeder akun Admin dan Guru default.
     */
    public function run(): void
    {
        (new DatabaseSeeder())->seedAccounts();
    }
}

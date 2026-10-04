<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Wrapper yang meneruskan ke DatabaseSeeder (sumber kebenaran tunggal).
 *
 * SEBELUMNYA seeder ini membuat/menimpa akun admin@smppresensipgri.sch.id
 * dengan password "password123", sedangkan DatabaseSeeder memakai
 * admin@presensi.com dengan "admin123". Dua kredensial admin yang berbeda
 * dalam satu aplikasi membuat login gagal secara membingungkan.
 *
 * Sekarang tidak ada lagi penulisan akun di sini; seluruh identitas dan
 * password mengikuti DatabaseSeeder sehingga tidak pernah saling menimpa.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Jalankan seeder akun Admin dan Guru default.
     */
    public function run(): void
    {
        (new DatabaseSeeder())->seedAccounts();
    }
}

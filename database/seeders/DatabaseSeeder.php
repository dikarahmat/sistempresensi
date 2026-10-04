<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SATU-SATUNYA SUMBER KEBENARAN AKUN LOGIN (2026-10-03).
 *
 * Seeding aman dijalankan berulang:
 *   - memakai updateOrCreate() berdasarkan `username`, bukan membuat duplikat;
 *   - TIDAK pernah truncate/delete tabel apa pun;
 *   - TIDAK pernah menyentuh data siswa, guru, kelas, presensi, atau settings.
 *
 * KREDENSIAL DEVELOPMENT (WAJIB DIGANTI SEBELUM PRODUCTION):
 *   admin / admin123
 *   guru  / guru123
 * `username` adalah identitas login. Email hanya atribut kontak tambahan.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Akun default yang dibuat/dipastikan ada.
     *
     * Struktur ini dipakai ulang oleh UserSeeder & AdminUserSeeder agar
     * `db:seed --class=UserSeeder` (atau AdminUserSeeder) tetap menghasilkan
     * kredensial yang sama persis - tidak ada lagi versi password berbeda.
     */
    public const ACCOUNTS = [
        [
            'username' => 'admin',
            'name' => 'Administrator',
            'role' => 'admin',
            'email' => 'admin@presensi.com',
            'password' => 'admin123',
        ],
        [
            'username' => 'guru',
            'name' => 'Guru Pengajar',
            'role' => 'guru',
            'email' => 'guru@presensi.com',
            'password' => 'guru123',
        ],
    ];

    public function run(): void
    {
        $this->seedAccounts();
    }

    /**
     * Pastikan akun admin & guru ada dengan kredensial yang konsisten.
     *
     * Idempotent: dijalankan berkali-kali tetap aman dan tidak mengubah
     * data lain. Password development di-set ulang ke nilai default di atas
     * setiap kali seeding, jadi tidak ada kebingungan antar seeder.
     */
    public function seedAccounts(): void
    {
        foreach (self::ACCOUNTS as $account) {
            User::updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'email' => $account['email'],
                    // WAJIB diganti sebelum production (lihat docblock).
                    'password' => Hash::make($account['password']),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
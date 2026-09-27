<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'username' => 'admin',
                'name' => 'Administrator',
                'role' => 'admin',
                'email' => 'admin@presensi.com',
            ],
            [
                'username' => 'guru',
                'name' => 'Guru Pengajar',
                'role' => 'guru',
                'email' => 'guru@presensi.com',
            ],
            [
                'username' => 'kesiswaan',
                'name' => 'Staff Kesiswaan',
                'role' => 'kesiswaan',
                'email' => 'kesiswaan@presensi.com',
            ],
        ];

        foreach ($accounts as $account) {
            // WAJIB diganti sebelum production, ini cuma untuk development/seeding awal
            User::updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'email' => $account['email'],
                    'password' => Hash::make(match($account['role']) {
                        'admin' => 'admin123',
                        'guru' => 'guru123',
                        'kesiswaan' => 'kesiswaan123',
                    }),
                ]
            );
        }
    }
}
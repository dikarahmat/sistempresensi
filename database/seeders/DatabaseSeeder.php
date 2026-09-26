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
            User::updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'email' => $account['email'],
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
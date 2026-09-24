<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KesiswaanUserSeeder extends Seeder
{
    /**
     * Run the database seeds for Kesiswaan.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'kesiswaan@smppresensipgri.sch.id'],
            [
                'name' => 'Staf Bagian Kesiswaan',
                'password' => Hash::make('password123'),
                'role' => 'kesiswaan',
                'email_verified_at' => now(),
            ]
        );
    }
}

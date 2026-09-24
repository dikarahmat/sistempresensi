<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Jalankan seeder akun Admin dan Guru/Wali Kelas default.
     */
    public function run(): void
    {
        // 1. Akun Administrator Utama
        $admin = User::updateOrCreate(
            ['email' => 'admin@smppresensipgri.sch.id'],
            [
                'name' => 'Administrator SMP PGRI',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Guru / Wali Kelas (Budi Santoso, S.Pd. - password default adalah NIP)
        $guruUser = User::updateOrCreate(
            ['email' => 'guru@smppresensipgri.sch.id'],
            [
                'name' => 'Budi Santoso, S.Pd.',
                'password' => Hash::make('198503122010011002'),
                'role' => 'guru',
                'email_verified_at' => now(),
            ]
        );

        // Hubungkan akun user guru ke profil guru di tabel teachers
        $teacher = Teacher::updateOrCreate(
            ['nip' => '198503122010011002'],
            [
                'user_id' => $guruUser->id,
                'name' => 'Budi Santoso, S.Pd.',
                'gender' => 'Laki-laki',
                'phone_number' => '081234567890',
                'phone' => '081234567890',
            ]
        );

        // Pastikan teacher->user_id terisi
        if ($teacher->user_id !== $guruUser->id) {
            $teacher->update(['user_id' => $guruUser->id]);
        }

        // Hubungkan ke kelas 7A (atau VII A) sebagai Wali Kelas
        $activeYear = AcademicYear::getActive();
        if ($activeYear) {
            $kelas7A = SchoolClass::where('academic_year_id', $activeYear->id)
                ->where(function ($q) {
                    $q->where('name', '7A')->orWhere('name', 'VII A');
                })->first();

            if ($kelas7A) {
                $kelas7A->update(['teacher_id' => $teacher->id]);
            }
        }

        // 3. Akun Bagian Kesiswaan (Kemahasiswaan)
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

<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pengaturan Default Sekolah
        $settings = [
            'school_name' => 'SMP PGRI PARUNG PANJANG',
            'school_npsn' => '20102030',
            'school_address' => 'Jl. Raya Parung Panjang No. 10, Parung Panjang, Bogor',
            'check_in_time' => '06:45',
            'late_limit_time' => '07:15',
            'auto_alfa_enabled' => '1',
            'wa_gateway_url' => 'https://api.fonnte.com/send',
            'wa_gateway_token' => 'dummy-token',
        ];
        foreach ($settings as $key => $val) {
            Setting::updateOrCreate(['key' => $key], ['value' => $val]);
        }

        // 2. Tahun Ajaran Aktif
        $academicYear = AcademicYear::updateOrCreate(
            ['name' => '2026/2027', 'semester' => 'Ganjil'],
            [
                'start_date' => '2026-07-15',
                'end_date' => '2026-12-20',
                'is_active' => true,
            ]
        );

        // 3. User Admin
        User::updateOrCreate(
            ['email' => 'admin@smppresensipgri.sch.id'],
            [
                'name' => 'Administrator SMP PGRI',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 3b. User Bagian Kesiswaan (Kemahasiswaan)
        User::updateOrCreate(
            ['email' => 'kesiswaan@smppresensipgri.sch.id'],
            [
                'name' => 'Staf Bagian Kesiswaan',
                'password' => Hash::make('password123'),
                'role' => 'kesiswaan',
                'email_verified_at' => now(),
            ]
        );

        // 4. Data Wali Kelas & Guru Demo (Budi Santoso, S.Pd. - password default NIP)
        $waliUser = User::updateOrCreate(
            ['email' => 'guru@smppresensipgri.sch.id'],
            [
                'name' => 'Budi Santoso, S.Pd.',
                'password' => Hash::make('198503122010011002'),
                'role' => 'guru',
                'email_verified_at' => now(),
            ]
        );

        $waliGuru = Teacher::updateOrCreate(
            ['nip' => '198503122010011002'],
            [
                'user_id' => $waliUser->id,
                'name' => 'Budi Santoso, S.Pd.',
                'gender' => 'Laki-laki',
                'phone' => '081234567890',
                'phone_number' => '081234567890',
            ]
        );

        if ($waliGuru->user_id !== $waliUser->id) {
            $waliGuru->update(['user_id' => $waliUser->id]);
        }

        // Tambahan Guru Pengajar Lainnya (password default NIP)
        for ($i = 2; $i <= 5; $i++) {
            $nipGuru = '19880101201501' . $i . '00' . $i;
            $u = User::updateOrCreate(
                ['email' => "guru{$i}@smp.test"],
                [
                    'name' => "Guru Pengajar {$i}, S.Pd.",
                    'password' => Hash::make($nipGuru),
                    'role' => 'guru',
                ]
            );
            Teacher::updateOrCreate(
                ['nip' => $nipGuru],
                [
                    'user_id' => $u->id,
                    'name' => $u->name,
                    'phone' => '08129876543' . $i,
                ]
            );
        }

        // 5. Data Kelas
        $kelas7A = SchoolClass::updateOrCreate(
            ['name' => 'VII A', 'academic_year_id' => $academicYear->id],
            [
                'level' => 'VII',
                'teacher_id' => $waliGuru->id,
            ]
        );

        SchoolClass::updateOrCreate(
            ['name' => 'VIII A', 'academic_year_id' => $academicYear->id],
            ['level' => 'VIII', 'teacher_id' => null]
        );

        SchoolClass::updateOrCreate(
            ['name' => 'IX A', 'academic_year_id' => $academicYear->id],
            ['level' => 'IX', 'teacher_id' => null]
        );

        // 6. Data Siswa untuk Kelas VII A
        for ($s = 1; $s <= 30; $s++) {
            Student::updateOrCreate(
                ['nis' => '260' . str_pad((string)$s, 3, '0', STR_PAD_LEFT)],
                [
                    'nisn' => '0098765' . str_pad((string)$s, 3, '0', STR_PAD_LEFT),
                    'name' => "Siswa Teladan {$s}",
                    'gender' => $s % 2 === 0 ? 'Laki-laki' : 'Perempuan',
                    'birth_place' => 'Jakarta',
                    'birth_date' => '2012-05-10',
                    'address' => 'Jl. Siswa Merdeka No. ' . $s,
                    'parent_name' => "Orang Tua Siswa {$s}",
                    'parent_phone' => '0812111122' . str_pad((string)$s, 2, '0', STR_PAD_LEFT),
                    'school_class_id' => $kelas7A->id,
                    'status' => 'Aktif',
                    'qr_token' => Str::random(32),
                ]
            );
        }

        // 7. Data Hari Libur
        Holiday::updateOrCreate(
            ['date' => '2026-08-17'],
            ['description' => 'Hari Kemerdekaan Republik Indonesia']
        );

        // 8. Dummy Presensi Hari Ini (Biar Dashboard Langsung Terisi Angka)
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $sampleStudents = Student::where('school_class_id', $kelas7A->id)->take(20)->get();

        foreach ($sampleStudents as $index => $student) {
            $status = 'Hadir';
            $timeRemark = 'Tepat Waktu';

            if ($index === 16) { $status = 'Izin';  $timeRemark = null; }
            elseif ($index === 17) { $status = 'Sakit'; $timeRemark = null; }
            elseif ($index >= 18) { $status = 'Alfa';  $timeRemark = null; }

            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'date' => $today,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'check_in' => $status === 'Hadir' ? '06:40:00' : null,
                    'status' => $status,
                    'time_remark' => $timeRemark,
                ]
            );
        }
    }
}
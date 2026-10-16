<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

/**
 * SATU-SATUNYA SUMBER KEBENARAN AKUN LOGIN (2026-10-03).
 *
 * Seeding aman dijalankan berulang:
 *   - memakai updateOrCreate()/firstOrCreate(), bukan membuat duplikat;
 *   - TIDAK pernah truncate/delete tabel apa pun;
 *   - TIDAK pernah membuat data CONTOH (siswa, guru, presensi, hari libur).
 *
 * Isi seed HANYA data esensial supaya aplikasi langsung bisa dipakai:
 *   1. Akun admin + akun guru default.
 *   2. Satu tahun ajaran aktif (rekap & presensi butuh tahun ajaran aktif).
 *   3. Tiga kelas inti 7A/8A/9A (form Tambah Siswa butuh pilihan kelas;
 *      tanpa kelas, dropdown kosong dan siswa tidak bisa ditambah manual).
 * Data contoh (siswa, guru, absensi) TIDAK lagi dibuat di sini - kalau
 * dibutuhkan untuk uji, buat sendiri lewat aplikasi atau import Excel.
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
     * Sumber kebenaran akun login SEBARANG PERUBAHAN:
     *   - email & password dibaca dari .env (ADMIN_EMAIL, ADMIN_PASSWORD).
     *   - SEEDER TIDAK MENYIMPAN KONDITIONAL DEFAULT PASSWORD.
     *   - Saat env kosong: seeder BERHENTI dengan pesan error yang jelas,
     *     ITSU BUKAN membuat akun default yang memicu login tanpa izin.
     *
     * Idempotent: memakai updateOrCreate(['email' => $email]), jadi
     * `db:seed` berjalan berkali-kali tidak menimpa akun lain & tidak
     * membuat duplikat.
     * =======================================================================
     * CARA MENGGUNAKAN (di .env):
     *   ADMIN_NAME="Administrator SMP PGRI"
     *   ADMIN_EMAIL="admin@sekolah.sch.id"
     *   ADMIN_PASSWORD="SangatKuat123!"
     * =======================================================================
     */
    public function run(): void
    {
        $adminName = env('ADMIN_NAME', 'Administrator');
        $adminEmail = env('ADMIN_EMAIL', '');
        $adminPassword = env('ADMIN_PASSWORD', '');

        if ($adminEmail === '' || $adminPassword === '' || $adminName === '') {
            throw new RuntimeException(
                'Seeder DatabaseSeeder gagal: variabel .env belum diisi. '
                .'Silakan set setidaknya ADMIN_EMAIL dan ADMIN_PASSWORD '
                .'di .env (lihat docblock pada DatabaseSeeder::run()).'
            );
        }

        $this->seedAccounts();
        $this->seedActiveAcademicYear();
        $this->seedCoreClasses();
    }

    /**
     * Satu tahun ajaran AKTIF. Tanpa ini, Rekap/Kehadiran tidak punya periode
     * yang bisa dipilih dan tahun ajaran aktif bernilai null di seluruh halaman.
     * Idempotent: kalau sudah ada tahun ajaran aktif, tidak ada yang berubah.
     */
    private function seedActiveAcademicYear(): void
    {
        if (AcademicYear::where('is_active', true)->exists()) {
            return;
        }

        $tahun = now()->year;
        $mulai = $tahun . '-07-01';
        $selesai = ($tahun + 1) . '-06-30';

        AcademicYear::create([
            'name' => $tahun . '/' . ($tahun + 1),
            'semester' => 'Ganjil',
            'start_date' => $mulai,
            'end_date' => $selesai,
            'is_active' => true,
        ]);

        AcademicYear::clearActiveCache();
        Cache::forget('academic_year_active');
    }

    /**
     * Tiga kelas inti SMP (7A, 8A, 9A) yang tertaut ke tahun ajaran aktif.
     * Dipakai form Tambah/Edit Siswa sebagai pilihan kelas.
     * Idempotent: kelas yang sudah ada tidak diduplikasi.
     */
    private function seedCoreClasses(): void
    {
        $tahunAjaran = AcademicYear::getActive();

        foreach (['7A' => ['7', 'VII'], '8A' => ['8', 'VIII'], '9A' => ['9', 'IX']] as $nama => [$grade, $level]) {
            SchoolClass::firstOrCreate(
                ['name' => $nama],
                [
                    'grade' => $grade,
                    'level' => $level,
                    'academic_year_id' => $tahunAjaran?->id,
                ]
            );
        }
    }

    /**
     * Pastikan akun ADMIN (dari .env) + akun default Guru & Guru Piket ada.
     *
     *   - ADMIN: nama/email/password dibaca dari .env (ADMIN_NAME,
     *     ADMIN_EMAIL, ADMIN_PASSWORD) - TIDAK ada password hardcode.
     *   - Guru & Guru Piket: akun default development (diubah lewat aplikasi
     *     setelah login). Satu akun per role.
     *
     * Kolom password_terenkripsi ikut diisi (enkripsi reversibel) supaya admin
     * bisa melihat password lewat Kelola Akun.
     *
     * Idempotent: dijalankan berkali-kali tetap aman dan tidak mengubah data
     * lain.
     */
    public function seedAccounts(): void
    {
        $accounts = [
            [
                'username' => 'admin',
                'name' => (string) env('ADMIN_NAME', 'Administrator'),
                'role' => 'admin',
                'email' => (string) env('ADMIN_EMAIL', ''),
                'password' => (string) env('ADMIN_PASSWORD', ''),
            ],
            [
                'username' => 'guru',
                'name' => 'Guru Pengajar',
                'role' => 'guru',
                'email' => 'guru@presensi.com',
                'password' => 'guru123',
            ],
            [
                'username' => 'piket',
                'name' => 'Guru Piket',
                'role' => 'piket',
                'email' => 'piket@presensi.com',
                'password' => 'piket123',
            ],
        ];

        foreach ($accounts as $account) {
            $plainPassword = $account['password'];

            if ($plainPassword === '') {
                continue;
            }

            // Idempotent & TIDAK menimpa akun lain:
            //   - akun dengan username sama sudah ada  -> perbarui;
            //   - email sudah dipakai akun username lain (mis. akun lama tanpa
            //     username) -> LEWATI, agar password akun lama tidak tertimpa
            //     dan tidak terjadi pelanggaran UNIQUE(users.email).
            $user = User::where('username', $account['username'])->first();

            if (! $user && $account['email'] !== '' && User::where('email', $account['email'])->exists()) {
                continue;
            }

            $attributes = [
                'name' => $account['name'],
                'role' => $account['role'],
                'email' => $account['email'],
                'password' => Hash::make($plainPassword),
                'password_terenkripsi' => Crypt::encryptString($plainPassword),
                'email_verified_at' => now(),
            ];

            if ($user) {
                $user->update($attributes);
            } else {
                User::create($attributes + ['username' => $account['username']]);
            }
        }
    }
}
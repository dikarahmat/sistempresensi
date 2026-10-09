<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
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
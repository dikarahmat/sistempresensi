<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * SATU-SATUNYA SUMBER KEBENARAN AKUN LOGIN (2026-10-03).
 *
 * Seeding aman dijalankan berulang:
 *   - memakai updateOrCreate()/firstOrCreate(), bukan membuat duplikat;
 *   - TIDAK pernah truncate/delete tabel apa pun;
 *   - TIDAK pernah membuat data CONTOH (siswa, guru, presensi, hari libur).
 *
 * Isi seed HANYA data esensial supaya aplikasi langsung bisa dipakai:
 *   1. Akun admin + akun guru + akun guru piket default.
 *   2. Satu tahun ajaran aktif (rekap & presensi butuh tahun ajaran aktif).
 *   3. Tiga kelas inti 7A/8A/9A (form Tambah Siswa butuh pilihan kelas;
 *      tanpa kelas, dropdown kosong dan siswa tidak bisa ditambah manual).
 * Data contoh (siswa, guru, absensi) TIDAK lagi dibuat di sini - kalau
 * dibutuhkan untuk uji, buat sendiri lewat aplikasi atau import Excel.
 *
 * IDENTITAS LOGIN = USERNAME (bukan email lagi).
 *
 * KREDENSIAL DEVELOPMENT (WAJIB DIGANTI SEBELUM PRODUCTION):
 *   admin / admin123
 *   guru  / guru123
 *   piket / piket123
 * Password di atas bisa ditimpa lewat .env (ADMIN_PASSWORD, GURU_PASSWORD,
 * PIKET_PASSWORD) atau langsung diubah lewat halaman Kelola Akun.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Password default untuk akun bawaan.
     *
     * Bisa ditimpa lewat .env:
     *   ADMIN_PASSWORD, GURU_PASSWORD, PIKET_PASSWORD
     */
    public const DEFAULT_PASSWORDS = [
        'admin' => 'admin123',
        'guru' => 'guru123',
        'piket' => 'piket123',
    ];

    /**
     * Akun default yang dibuat/dipastikan ada.
     *
     * Username adalah identitas login. Email hanya atribut kontak tambahan
     * (boleh kosong) dan TIDAK lagi dipakai untuk masuk ke aplikasi.
     *
     * Idempotent: memakai updateOrCreate(['username' => ...]), jadi
     * `db:seed` berjalan berkali-kali tidak menimpa akun lain & tidak
     * membuat duplikat.
     */
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
     * Pastikan akun ADMIN + akun default Guru & Guru Piket ada.
     *
     *   - Identitas login: USERNAME (admin / guru / piket).
     *   - Password: default development (lihat self::DEFAULT_PASSWORDS),
     *     bisa ditimpa lewat .env (ADMIN_PASSWORD / GURU_PASSWORD /
     *     PIKET_PASSWORD) atau diubah lewat aplikasi.
     *   - Email: atribut kontak tambahan saja, boleh kosong.
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
                'role' => 'admin',
                'email' => (string) env('ADMIN_EMAIL', ''),
                'password' => (string) (env('ADMIN_PASSWORD') ?: self::DEFAULT_PASSWORDS['admin']),
            ],
            [
                'username' => 'guru',
                'role' => 'guru',
                'email' => (string) env('GURU_EMAIL', 'guru@presensi.com'),
                'password' => (string) (env('GURU_PASSWORD') ?: self::DEFAULT_PASSWORDS['guru']),
            ],
            [
                'username' => 'piket',
                'role' => 'piket',
                'email' => (string) env('PIKET_EMAIL', 'piket@presensi.com'),
                'password' => (string) (env('PIKET_PASSWORD') ?: self::DEFAULT_PASSWORDS['piket']),
            ],
        ];

        foreach ($accounts as $account) {
            $plainPassword = $account['password'];

            if ($plainPassword === '') {
                continue;
            }

            // Idempotent & TIDAK menimpa akun lain:
            //   - akun dengan username sama sudah ada  -> perbarui;
            //   - akun dengan username lain tidak ikut tersentuh.
            $user = User::where('username', $account['username'])->first();

            $attributes = [
                // Kolom `name` tidak lagi dipakai untuk tampilan dan tidak
                // diedit lagi; nilainya otomatis mengikuti username (kolomnya
                // tetap ada & wajib terisi, tidak dihapus).
                'name' => $account['username'],
                'role' => $account['role'],
                'password' => Hash::make($plainPassword),
                'password_terenkripsi' => Crypt::encryptString($plainPassword),
                'email_verified_at' => now(),
            ];

            // Email hanya atribut kontak tambahan. Kolom email tetap UNIQUE,
            // jadi hanya diisi bila belum dipakai akun lain.
            $email = trim($account['email']);

            if ($email !== '' && ! User::where('email', $email)
                ->when($user, fn ($query) => $query->where('id', '!=', $user->id))
                ->exists()) {
                $attributes['email'] = $email;
            }

            if ($user) {
                $user->update($attributes);
            } else {
                User::create($attributes + ['username' => $account['username']]);
            }
        }

        // Peringatan sekali jalan: password default development harus diganti.
        Log::warning(
            'SEED: akun default memakai password development. '
            .'Ganti lewat .env (ADMIN_PASSWORD/GURU_PASSWORD/PIKET_PASSWORD) '
            .'atau lewat halaman Kelola Akun sebelum dipakai di production.'
        );
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Redirect root ke dashboard sesuai role (admin/guru), atau ke login.
     * Dipisah dari closure supaya route:cache bisa berjalan.
     */
    public function redirectRoot(Request $request)
    {
        if (Auth::check()) {
            return match (Auth::user()->role) {
                'admin' => redirect()->route('admin.dashboard'),
                'guru' => redirect()->route('guru.dashboard'),
                'piket' => redirect()->route('piket.absensi.index'),
                default => redirect()->route('login'),
            };
        }
        return redirect()->route('login');
    }

    /**
     * Tampilkan form login universal (tanpa tab role terpisah).
     */
    public function showLoginForm(Request $request)
    {
        $this->warnIfUsersTableEmpty();

        if (Auth::check()) {
            // Akun NONAKTIF yang masih membawa sesi lama: keluarkan paksa
            // supaya pesan "akun tidak dapat digunakan" tampil di halaman login.
            if (!Auth::user()->isActive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return view('auth.login')->withErrors([
                    'login' => 'Akun tidak dapat digunakan. Hubungi admin sekolah.',
                ]);
            }

            // Role yang dikenal: langsung arahkan ke dashboard panelnya.
            // Role tak dikenal: logout agar tidak terjadi loop redirect ke /login.
            if (in_array(Auth::user()->role, ['admin', 'guru', 'piket'], true)) {
                return $this->redirectBasedOnRole(Auth::user());
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return view('auth.login');
    }

    /**
     * Peringatan dini bila tabel `users` kosong.
     *
     * Gejala "Username atau kata sandi yang Anda masukkan salah" hampir selalu
     * bermula dari tabel users yang ikut terhapus. Karena itu, saat halaman
     * login dibuka, kondisi ini dicatat ke log agar masalah cepat terdeteksi.
     *
     * PENTING:
     *   - peringatan hanya ditulis ke LOG, tidak pernah ditampilkan ke publik;
     *   - aplikasi TIDAK PERNAH membuat akun otomatis, termasuk di production,
     *     supaya tidak menjadi celah keamanan.
     *
     * Hanya dicatat pada environment local/testing agar log production tetap
     * bersih dan tidak membocorkan informasi struktur database.
     */
    protected function warnIfUsersTableEmpty(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        try {
            if (! Schema::hasTable('users')) {
                Log::warning('AUTH: tabel users belum ada. Jalankan `php artisan migrate`.');
                return;
            }

            if (User::count() === 0) {
                Log::warning(
                    'AUTH: tabel users KOSONG - tidak ada akun yang bisa login. '
                    .'Kemungkinan besar database ter-reset (mis. migrate:fresh atau '
                    .'seeder/fitur yang menghapus massal). Jalankan `php artisan db:seed` '
                    .'untuk membuat ulang akun admin/guru.'
                );
            }
        } catch (\Throwable $e) {
            // Deteksi dini tidak boleh menjatuhkan halaman login.
            report($e);
        }
    }

    /**
     * Router login universal & cerdas:
     * - Menerima satu input Email, Username, atau NIP
     * - Mendeteksi hak akses secara otomatis (ADMIN atau GURU)
     * - Mengarahkan ke dashboard masing-masing secara instan tanpa perlu memilih role manual
     */
    public function login(Request $request)
    {
                $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginInput = trim((string) $request->input('login'));
        $password = (string) $request->input('password');
        $remember = $request->boolean('remember');

        // 1. Cek apakah login menggunakan Email User lengkap (atau username pendek)
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $loginInput)->first();
            if ($user && $this->verifyUserCredentials($user, $password)) {
                if (!$user->isActive()) {
                    return back()->withInput($request->only('login', 'remember'))->withErrors([
                        'login' => 'Akun tidak dapat digunakan. Hubungi admin sekolah.',
                    ]);
                }

                Auth::login($user, $remember);
                $request->session()->regenerate();

                return $this->redirectBasedOnRole($user);
            }
        }

        // 2. Cek apakah login menggunakan Username pendek (misal: 'admin', 'guru')
        //    Hanya query username jika kolom sudah ada di database.
        $user = null;
        if (Schema::hasColumn('users', 'username')) {
            $user = User::where('username', $loginInput)->first();
        }
        if (!$user) {
            // Prefix alias email: 'admin' -> 'admin@%'
            $user = User::where('email', 'like', $loginInput . '@%')->first();
        }

        if ($user && $this->verifyUserCredentials($user, $password)) {
            if (!$user->isActive()) {
                return back()->withInput($request->only('login', 'remember'))->withErrors([
                    'login' => 'Akun tidak dapat digunakan. Hubungi admin sekolah.',
                ]);
            }

            Auth::login($user, $remember);
            $request->session()->regenerate();

            return $this->redirectBasedOnRole($user);
        }

        // 3. Auth::attempt sebagai fallback terakhir (untuk username/email yang valid)
        $attemptField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        if (Schema::hasColumn('users', 'username') || $attemptField === 'email') {
            if (Auth::attempt([$attemptField => $loginInput, 'password' => $password], $remember)) {
                // Pengecekan nonaktif juga berlaku di jalur fallback ini.
                if (!Auth::user()->isActive()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withInput($request->only('login', 'remember'))->withErrors([
                        'login' => 'Akun tidak dapat digunakan. Hubungi admin sekolah.',
                    ]);
                }

                $request->session()->regenerate();

                return $this->redirectBasedOnRole(Auth::user());
            }
        }

        // Autentikasi Gagal
        return back()->withInput($request->only('login', 'remember'))->withErrors([
            'login' => 'Email atau kata sandi yang Anda masukkan salah.',
        ]);
    }

    /**
     * Verifikasi kredensial user umum.
     */
    protected function verifyUserCredentials(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    /**
     * Helper redirect otomatis dan cerdas ke dashboard berdasarkan role pengguna di database.
     */
    protected function redirectBasedOnRole(User $user)
    {
        return match ($user->role) {
            'admin' => redirect()->intended(route('admin.dashboard')),
            'guru' => redirect()->intended(route('guru.dashboard')),
            // Guru Piket langsung masuk ke halaman Presensi (scan QR) -
            // tidak punya Dashboard dan aksesnya dibatasi Kehadiran+Presensi.
            'piket' => redirect()->intended(route('piket.absensi.index')),
            default => redirect()->route('login')->withErrors([
                'login' => 'Role pengguna tidak dikenali atau tidak memiliki hak akses.',
            ]),
        };
    }

    /**
     * Logout pengguna dan invalidasi sesi secara aman.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
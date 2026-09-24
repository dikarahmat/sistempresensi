<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Tampilkan form login universal (tanpa tab role terpisah).
     */
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Router login universal & cerdas:
     * - Menerima satu input Email, Username, atau NIP
     * - Mendeteksi hak akses secara otomatis (Admin, Guru / Wali Kelas, Kesiswaan)
     * - Mengarahkan ke dashboard masing-masing secara instan tanpa perlu memilih role manual
     */
    public function login(Request $request)
    {
        // Dukung input universal 'login' maupun legacy parameter ('nip' / 'email')
        if (!$request->has('login') && $request->has('nip')) {
            $request->merge(['login' => $request->input('nip')]);
        } elseif (!$request->has('login') && $request->has('email')) {
            $request->merge(['login' => $request->input('email')]);
        }

        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Email atau Username wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginInput = trim((string) $request->input('login'));
        $password = (string) $request->input('password');
        $remember = $request->boolean('remember');

        // 1. Cek apakah login menggunakan NIP Guru
        $teacher = Teacher::where('nip', $loginInput)->first();
        if ($teacher) {
            $teacherUser = $this->resolveTeacherUser($teacher);
            if ($teacherUser && $this->verifyTeacherPassword($teacherUser, $teacher, $password)) {
                Auth::login($teacherUser, $remember);
                $request->session()->regenerate();

                return redirect()->intended(route('guru.dashboard'));
            }
        }

        // 2. Cek apakah login menggunakan Email User lengkap
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $loginInput)->first();
            if ($user && $this->verifyUserCredentials($user, $password)) {
                Auth::login($user, $remember);
                $request->session()->regenerate();

                return $this->redirectBasedOnRole($user);
            }
        }

        // 3. Cek apakah login menggunakan Name / Username atau prefix alias email (misal: 'admin', 'kesiswaan')
        $user = User::where('name', $loginInput)
            ->orWhere('email', $loginInput)
            ->orWhere('email', 'like', $loginInput . '@%')
            ->first();

        if ($user && $this->verifyUserCredentials($user, $password)) {
            Auth::login($user, $remember);
            $request->session()->regenerate();

            return $this->redirectBasedOnRole($user);
        }

        // 4. Standar Auth::attempt sebagai fallback cerdas
        $attemptField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        if (Auth::attempt([$attemptField => $loginInput, 'password' => $password], $remember)) {
            $request->session()->regenerate();

            return $this->redirectBasedOnRole(Auth::user());
        }

        // 5. Cek jika input adalah NISN Siswa
        if (Student::where('nisn', $loginInput)->exists()) {
            return back()->withInput($request->only('login', 'remember'))->withErrors([
                'login' => 'Portal mandiri siswa saat ini sedang disiapkan oleh pihak sekolah.',
            ]);
        }

        // 6. Autentikasi Gagal
        return back()->withInput($request->only('login', 'remember'))->withErrors([
            'login' => 'Email/Username atau kata sandi yang Anda masukkan salah.',
        ]);
    }

    /**
     * Dapatkan atau sinkronkan akun User untuk Guru berdasarkan data Teacher.
     */
    protected function resolveTeacherUser(Teacher $teacher): ?User
    {
        if ($teacher->user_id) {
            $user = User::find($teacher->user_id);
            if ($user) {
                if ($user->role !== 'guru') {
                    $user->update(['role' => 'guru']);
                }
                return $user;
            }
        }

        $userEmail = $teacher->nip . '@guru.smppresensipgri.sch.id';
        $user = User::where('email', $userEmail)->first();

        if (!$user) {
            $user = User::create([
                'name' => $teacher->name,
                'email' => $userEmail,
                'password' => Hash::make($teacher->nip),
                'role' => 'guru',
                'email_verified_at' => now(),
            ]);
        } else {
            if ($user->role !== 'guru') {
                $user->update(['role' => 'guru']);
            }
        }

        $teacher->update(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Verifikasi kata sandi akun Guru dengan enkripsi standar.
     */
    protected function verifyTeacherPassword(User $user, Teacher $teacher, string $password): bool
    {
        return Hash::check($password, $user->password);
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
            'kesiswaan' => redirect()->intended(route('kesiswaan.dashboard')),
            default => redirect()->route('login')->withErrors([
                'login' => 'Role pengguna tidak dikenali atau tidak memiliki hak akses.',
            ]),
        };
    }

    /**
     * Kompatibilitas untuk legacy route /login/guru
     */
    public function loginGuru(Request $request)
    {
        return $this->login($request);
    }

    /**
     * Kompatibilitas untuk legacy route /login/admin
     */
    public function loginAdmin(Request $request)
    {
        return $this->login($request);
    }

    /**
     * Kompatibilitas untuk legacy route /login/kesiswaan
     */
    public function loginKesiswaan(Request $request)
    {
        return $this->login($request);
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
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAccountController extends Controller
{
    /**
     * Daftar akun login.
     *
     * Aturan tampilan (Bagian 1 & 3):
     *   - SATU akun per role (admin, guru, piket).
     *   - Diurutkan berdasarkan waktu dibuat (id menaik): akun paling lama
     *     paling atas. Akun ganda per role (masih ada karena migration
     *     pembersih belum dijalankan) dibatasi ke id TERKECIL.
     */
    public function index(): View
    {
        $users = User::query()
            ->orderBy('id')
            ->get()
            ->unique('role')
            ->values();

        // Role yang BELUM punya akun (untuk dropdown Tambah).
        $usedRoles = $users->pluck('role')->all();
        $roleOptions = array_diff_key(User::ROLES, array_flip($usedRoles));

        return view('admin.accounts.index', [
            'users' => $users,
            'roleOptions' => $roleOptions,
            'roleLabels' => User::ROLES,
        ]);
    }

    /**
     * Simpan akun baru (slot role yang masih kosong).
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rulesForStore(), $this->messages());

        // Guard: role belum punya akun (satu akun per role).
        if (User::where('role', $validated['role'])->exists()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => ['role' => ['Role ini sudah memiliki akun.']]], 422);
            }
            return redirect()->route('admin.accounts.index')->with('error', __('Role ini sudah memiliki akun.'));
        }

        // Kolom password_terenkripsi ditambahkan migration terpisah. Bila
        // migration belum dijalankan, simpan tetap jalan tanpa kolom itu
        // (admin akan diminta menjalankan migrasi, lihat pesan getEncryptedPassword).
        $hasEncryptedColumn = Schema::hasColumn('users', 'password_terenkripsi');

        try {
            $user = DB::transaction(function () use ($validated, $hasEncryptedColumn) {
                $password = $validated['password'];
                $username = strtolower(trim($validated['username']));

                $attributes = [
                    // Kolom `name` tidak lagi dipakai untuk tampilan dan tidak
                    // diedit lagi; nilainya otomatis mengikuti username sehingga
                    // kolom itu tetap terisi (kolomnya TIDAK dihapus).
                    'name' => $username,
                    'username' => $username,
                    'role' => $validated['role'],
                    'status' => User::STATUS_AKTIF,
                    'password' => $password,
                    'email_verified_at' => now(),
                ];

                if ($hasEncryptedColumn) {
                    $attributes['password_terenkripsi'] = Crypt::encryptString($password);
                }

                $user = User::create($attributes);
                $this->cycleRememberToken($user);

                return $user;
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Akun berhasil ditambahkan.',
                    'user' => $user->only(['id', 'name', 'username', 'role', 'status']),
                ]);
            }
            return redirect()->route('admin.accounts.index')->with('success', __('Akun berhasil ditambahkan.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Terjadi kesalahan.'], 500);
            }
            return redirect()->route('admin.accounts.index')->with('error', __('Terjadi kesalahan saat menambah akun.'));
        }
    }

    /**
     * Perbarui akun (username dan/atau password).
     *
     * Role SENGAJA tidak bisa diubah lewat form: nilai role diambil dari
     * akun yang ada (bukan dari input).
     *
     * Nama SENGAJA tidak bisa diubah lewat form lagi: kolom `name` tidak lagi
     * dipakai untuk tampilan dan nilainya otomatis mengikuti username.
     *
     * Hanya simpan password BARU jika password admin saat ini benar.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate($this->rulesForUpdate($user->id), $this->messages());

        $username = strtolower(trim($validated['username']));

        $data = [
            // Kolom `name` tidak boleh ditimpa dengan null/kosong: nilainya
            // otomatis mengikuti username.
            'name' => $username,
            'username' => $username,
            'role' => $user->role,
        ];

        $passwordChanged = false;
        $hasEncryptedColumn = Schema::hasColumn('users', 'password_terenkripsi');

        // Hanya perbarui password kalau field kosong / password baru diisi
        // DAN password admin saat ini diverifikasi benar.
        if (isset($validated['password_current'])) {
            if (empty($validated['password']) || !Hash::check((string) $validated['password_current'], $user->password)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Password admin saat ini salah. Password baru TIDAK disimpan.',
                    ], 422);
                }
                return redirect()->route('admin.accounts.index')
                    ->withInput()
                    ->with('error', __('Password admin saat ini salah. Password baru TIDAK disimpan.'));
            }

            if (!empty($validated['password'])) {
                $data['password'] = $validated['password'];
                if ($hasEncryptedColumn) {
                    $data['password_terenkripsi'] = Crypt::encryptString($validated['password']);
                }
                $passwordChanged = true;
            }
        }

        try {
            DB::transaction(function () use ($user, $data, $passwordChanged) {
                $user->update($data);

                if ($passwordChanged) {
                    $this->cycleRememberToken($user);
                }
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Akun berhasil diperbarui.',
                    'user' => $user->only(['id', 'name', 'username', 'role', 'status']),
                ]);
            }
            return redirect()->route('admin.accounts.index')->with('success', __('Akun berhasil diperbarui.'));
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Terjadi kesalahan.'], 500);
            }
            return redirect()->route('admin.accounts.index')->with('error', __('Terjadi kesalahan saat memperbarui akun.'));
        }
    }

    /**
     * Kembalikan password tersimpan (hasil dekripsi) untuk satu akun.
     *
     * Dipakai modal Edit. Gagal dekripsi / kolom kosong / kolom belum ada
     * semuanya dianggap "belum tersimpan" sehingga admin bisa mengisinya.
     */
    public function getEncryptedPassword(Request $request, User $user): JsonResponse
    {
        // Bila kolom belum ada (migration belum dijalankan), jangan 500.
        if (!Schema::hasColumn('users', 'password_terenkripsi')) {
            return response()->json([
                'encrypted' => '',
                'decrypted' => '',
                'stored' => false,
                'message' => 'Database belum diperbarui. Jalankan migrasi.',
            ])->header('Cache-Control', 'no-store');
        }

        $stored = $user->password_terenkripsi;

        if (empty($stored)) {
            return response()->json([
                'encrypted' => '',
                'decrypted' => '',
                'stored' => false,
                'message' => 'Password lama belum tersimpan.',
            ])->header('Cache-Control', 'no-store');
        }

        try {
            $decrypted = Crypt::decryptString($stored);
        } catch (\Throwable $e) {
            return response()->json([
                'encrypted' => '',
                'decrypted' => '',
                'stored' => false,
                'message' => 'Password lama belum tersimpan.',
            ])->header('Cache-Control', 'no-store');
        }

        return response()->json([
            'encrypted' => $stored,
            'decrypted' => $decrypted,
            'stored' => true,
            'message' => 'Password ditemukan.',
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Hapus akun secara PERMANEN.
     *
     * Guard (dicek SEBELUM menghapus):
     *   - bukan akun sendiri;
     *   - bukan akun ber-role admin.
     * Relasi users.id dilepas dulu (teachers.user_id -> NULL, sessions dihapus)
     * supaya data guru/presensi tetap utuh.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'], 403);
            }
            return redirect()->route('admin.accounts.index')->with('error', __('Anda tidak dapat menghapus akun Anda sendiri.'));
        }

        if ($user->role === User::ROLE_ADMIN) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Akun Admin tidak dapat dihapus.'], 403);
            }
            return redirect()->route('admin.accounts.index')->with('error', __('Akun Admin tidak dapat dihapus.'));
        }

        try {
            DB::transaction(function () use ($user) {
                if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'user_id')) {
                    DB::table('teachers')->where('user_id', $user->id)->update(['user_id' => null]);
                }
                if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                }
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
                $user->delete();
            });

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Akun berhasil dihapus.']);
            }
            // Notifikasi HAPUS memakai flash 'deleted' (alert MERAH + ikon
            // tempat sampah). Flash 'success' (hijau) hanya untuk tambah/ubah.
            return redirect()->route('admin.accounts.index')->with('deleted', __('Akun berhasil dihapus.'));
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Terjadi kesalahan.'], 500);
            }
            return redirect()->route('admin.accounts.index')->with('error', __('Terjadi kesalahan saat menghapus akun.'));
        }
    }

    protected function rulesForStore(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => array_merge($this->usernameRules(), [
                Rule::unique('users', 'username'),
            ]),
            'role' => ['required', 'in:' . implode(',', array_keys(User::ROLES))],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    protected function rulesForUpdate(int $userId): array
    {
        return [
            // Tidak ada validasi `name`: field Nama sudah dihapus dari modal
            // Edit dan kolom `name` mengikuti username.
            'username' => array_merge($this->usernameRules(), [
                // Saat edit, akun yang sedang diedit diabaikan supaya
                // username lamanya tidak dianggap duplikat.
                Rule::unique('users', 'username')->ignore($userId),
            ]),
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    /**
     * Aturan username (identitas login).
     *
     * Wajib, minimal 3 karakter, tanpa spasi, dan hanya boleh berisi
     * huruf kecil, angka, titik, underscore, atau strip.
     */
    protected function usernameRules(): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:255',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value)) {
                    return;
                }

                if (preg_match('/\s/', $value)) {
                    $fail('Username tidak boleh mengandung spasi.');

                    return;
                }

                if (! preg_match('/^[a-z0-9._-]+$/', $value)) {
                    $fail('Username hanya boleh berisi huruf kecil, angka, titik, underscore, atau strip.');
                }
            },
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal 3 karakter.',
            'username.max' => 'Username maksimal 255 karakter.',
            'username.unique' => 'Username sudah digunakan akun lain.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }

    private function cycleRememberToken(User $user): void
    {
        $user->forceFill([
            'remember_token' => Str::random(60),
        ])->save();
    }
}

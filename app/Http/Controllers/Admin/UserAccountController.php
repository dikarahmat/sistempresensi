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

                $attributes = [
                    'name' => trim($validated['name']),
                    'email' => strtolower(trim($validated['email'])),
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
                    'user' => $user->only(['id', 'name', 'email', 'role', 'status']),
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
     * Perbarui akun (nama, email, dan/atau password).
     *
     * Role SENGAJA tidak bisa diubah lewat form: nilai role diambil dari
     * akun yang ada (bukan dari input).
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate($this->rulesForUpdate($user->id), $this->messages());

        $data = [
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => $user->role,
        ];

        $passwordChanged = false;
        $hasEncryptedColumn = Schema::hasColumn('users', 'password_terenkripsi');

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
            if ($hasEncryptedColumn) {
                $data['password_terenkripsi'] = Crypt::encryptString($validated['password']);
            }
            $passwordChanged = true;
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
                    'user' => $user->only(['id', 'name', 'email', 'role', 'status']),
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:' . implode(',', array_keys(User::ROLES))],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    protected function rulesForUpdate(int $userId): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar pada akun lain.',
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

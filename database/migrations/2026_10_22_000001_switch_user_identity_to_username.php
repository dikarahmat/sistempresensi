<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ganti identitas login akun dari EMAIL menjadi USERNAME.
 *
 * Tahapan (berurutan, wajib):
 *   1. Pastikan kolom `users.username` ada (string, unique).
 *   2. Isi username akun yang SUDAH ADA supaya tidak ada akun rusak:
 *        - akun guru yang tertaut ke data guru  -> NIP (login NIP + password)
 *        - akun default per role                 -> admin / guru / piket
 *        - akun lain                             -> slug nama (dib uniquify)
 *      Username yang sudah terisi TIDAK diubah, supaya identitas login lama
 *      milik pengguna tidak ikut berubah diam-diam.
 *   3. Pastikan index UNIQUE username tersedia.
 *   4. `username` menjadi NOT NULL (semua baris sudah terisi pada langkah 2).
 *   5. `email` menjadi NULLABLE. Kolom email TIDAK dihapus (aman untuk data
 *      lama & fitur lain), hanya tidak lagi dipakai sebagai identitas login
 *      maupun pada form.
 *
 * Catatan: email yang kosong/NULL tetap boleh karena kolom email dibuat
 * nullable - tidak ada baris yang dihapus atau diubah datanya.
 */
return new class extends Migration
{
    /**
     * Username default untuk satu akun per role.
     */
    private const ROLE_USERNAMES = [
        'admin' => 'admin',
        'guru' => 'guru',
        'piket' => 'piket',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        // 1. Kolom username (nullable dulu supaya bisa diisi bertahap).
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable();
            });
        }

        // 2. Isi username akun yang sudah ada.
        $this->fillUsernames();

        // 3. Index UNIQUE username.
        if (! $this->hasUniqueUsernameIndex()) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('username');
            });
        }

        // 4. username NOT NULL.
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });

        // 5. email NULLABLE (kolom tetap ada, tidak dihapus).
        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable()->change();
            });
        }
    }

    /**
     * Apakah tabel `users` sudah punya index UNIQUE pada kolom username?
     *
     * Dicek per-kolom (bukan per-nama index) supaya aman untuk MySQL maupun
     * SQLite: SQLite menyimpan daftar kolom index sebagai satu string.
     */
    protected function hasUniqueUsernameIndex(): bool
    {
        foreach (Schema::getIndexes('users') as $index) {
            if (empty($index['unique'])) {
                continue;
            }

            $columns = array_map('strtolower', (array) $index['columns']);

            if (in_array('username', $columns, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Isi username untuk akun lama.
     *
     * - Akun guru yang punya NIP  -> username = NIP (login NIP + password).
     * - Akun dengan username kosong -> username default role (admin/guru/piket).
     * - Sisanya                    -> slug dari nama + angka bila bentrok.
     *
     * Username yang sudah terisi dibiarkan apa adanya.
     */
    protected function fillUsernames(): void
    {
        $users = DB::table('users')
            ->select(['id', 'name', 'username', 'role'])
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        // NIP per akun guru (dipakai sebagai username agar login lama tetap jalan).
        $nipByUserId = $this->nipByUserId();

        // Username yang sudah dipakai (akun yang akan dipertahankan).
        $taken = [];
        foreach ($users as $user) {
            $current = trim((string) ($user->username ?? ''));
            if ($current !== '') {
                $taken[strtolower($current)] = true;
            }
        }

        foreach ($users as $user) {
            $current = trim((string) ($user->username ?? ''));

            // Sudah punya username -> jangan diubah (login lama tetap aman).
            if ($current !== '') {
                continue;
            }

            $desired = $this->desiredUsername($user, $nipByUserId, $taken);

            DB::table('users')->where('id', $user->id)->update(['username' => $desired]);

            $taken[strtolower($desired)] = true;
        }
    }

    /**
     * NIP untuk setiap akun yang tertaut ke tabel teachers.
     *
     * @return array<int, string>
     */
    protected function nipByUserId(): array
    {
        if (! Schema::hasTable('teachers')
            || ! Schema::hasColumn('teachers', 'user_id')
            || ! Schema::hasColumn('teachers', 'nip')) {
            return [];
        }

        $map = [];

        DB::table('teachers')
            ->select(['user_id', 'nip'])
            ->whereNotNull('user_id')
            ->whereNotNull('nip')
            ->where('nip', '!=', '')
            ->orderBy('id')
            ->get()
            ->each(function ($teacher) use (&$map) {
                $map[(int) $teacher->user_id] = (string) $teacher->nip;
            });

        return $map;
    }

    /**
     * Tentukan username untuk satu akun lama.
     *
     * @param  \Illuminate\Support\Collection  $user
     * @param  array<int, string>  $nipByUserId
     * @param  array<string, bool>  $taken
     */
    protected function desiredUsername($user, array $nipByUserId, array $taken): string
    {
        // 1. Akun guru: pakai NIP supaya login NIP + password tetap berlaku.
        $nip = $this->sanitizeUsername($nipByUserId[(int) $user->id] ?? '');

        if ($nip !== '' && strlen($nip) >= 3 && ! isset($taken[$nip])) {
            return $nip;
        }

        // 2. Akun default per role: admin / guru / piket.
        $roleDefault = self::ROLE_USERNAMES[(string) $user->role] ?? '';

        if ($roleDefault !== '' && ! isset($taken[$roleDefault])) {
            return $roleDefault;
        }

        // 3. Akun lain: slug dari nama, lalu uniquify bila bentrok.
        $base = $this->sanitizeUsername((string) $user->name);
        $base = $base !== '' ? $base : 'user';

        if (strlen($base) < 3) {
            $base = 'user';
        }

        if (! isset($taken[$base])) {
            return $base;
        }

        $suffix = 2;
        while (isset($taken[$base . $suffix])) {
            $suffix++;
        }

        return $base . $suffix;
    }

    /**
     * Bersihkan teks menjadi username yang valid:
     * huruf kecil, angka, titik, underscore, strip (tanpa spasi).
     */
    protected function sanitizeUsername(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9._-]+/', '', $value) ?? '';
        $value = trim($value, '.-_');

        return $value;
    }

    /**
     * Tidak ada rollback: kolom `username` yang sudah terisi tidak mungkin
     * dikembalikan ke kosong tanpa merusak identitas login akun.
     */
    public function down(): void
    {
        //
    }
};

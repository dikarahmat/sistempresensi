<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pembersih akun & role lama (sistem hanya mengenal dua role: ADMIN dan GURU).
     *
     * Aman terhadap foreign key:
     *   1. Relasi teachers.user_id ke akun yang akan dihapus dilepas lebih dulu (set NULL),
     *      karena kolom tersebut memakai foreign key ke tabel users.
     *   2. Sesi login milik akun tersebut dibersihkan dari tabel sessions.
     *   3. Baru baris akun dihapus dari tabel users.
     *   4. Kolom users.role dinormalisasi menjadi varchar(50) sehingga nilai role lama
     *      (ENUM) tidak mungkin tersimpan lagi.
     *
     * Akun ADMIN dan GURU tidak disentuh sama sekali.
     */
    public function up(): void
    {
        // Semua akun dengan role di luar admin & guru = akun role lama yang dihapus.
        $userIds = DB::table('users')
            ->whereNotIn('role', ['admin', 'guru'])
            ->pluck('id');

        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'user_id')) {
            DB::table('teachers')
                ->whereIn('user_id', $userIds->all() ?: [0])
                ->update(['user_id' => null]);
        }

        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')
                ->whereIn('user_id', $userIds->all() ?: [0])
                ->delete();
        }

        if ($userIds->isNotEmpty()) {
            DB::table('users')->whereIn('id', $userIds)->delete();
        }

        // Normalisasi kolom role: bila sebelumnya bertipe ENUM dengan nilai role lama,
        // tipe diubah menjadi varchar(50) sehingga nilai tersebut tidak bisa dipakai lagi.
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 50)->default('guru')->change();
            });
        }
    }

    /**
     * Tidak ada rollback: akun & role lama sengaja dihapus permanen.
     * (Salinan database sebelum pembersihan tersedia sebagai dump SQL di luar project.)
     */
    public function down(): void
    {
        //
    }
};

<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use RuntimeException;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // ========================================================================
    // DAFTAR ROLE TERPUSAT (Single Source of Truth)
    // Dropdown form, validasi controller, dan middleware semuanya memakai
    // daftar ini. Menambah role baru cukup menambah satu baris di sini
    // + mendaftarkan route group-nya di routes/web.php.
    // ========================================================================
    public const ROLE_ADMIN = 'admin';
    public const ROLE_GURU = 'guru';
    public const ROLE_PIKET = 'piket';

    /** @var array<string, string> Nilai role => label tampilan (Bahasa Indonesia). */
    public const ROLES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_GURU => 'Guru',
        self::ROLE_PIKET => 'Guru Piket',
    ];

    // ========================================================================
    // STATUS AKUN (Kelola Akun)
    // Akun NONAKTIF tidak bisa login dan sesi aktifnya ditolak middleware
    // EnsureUserRole pada request berikutnya. Tidak ada penghapusan permanen
    // supaya riwayat data tetap utuh.
    // ========================================================================
    public const STATUS_AKTIF = 'Aktif';
    public const STATUS_NONAKTIF = 'Nonaktif';

    /**
     * Akun yang tidak boleh dihapus lewat aplikasi.
     *
     * Admin adalah satu-satunya akun yang bisa mengelola data sekolah.
     * Jika akun ini terhapus, aplikasi terkunci permanen karena tidak ada
     * lagi yang bisa membuat akun baru (dan halaman login sengaja tidak
     * pernah membuat akun otomatis, termasuk di production).
     */
    public const PROTECTED_USERNAMES = ['admin'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'role',
        'status',
        'password',
        'password_terenkripsi',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'password_terenkripsi',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        // Penjaga terakhir tabel `users`.
        //
        // Fitur hapus massal di aplikasi (hapus semua siswa, hapus semua guru,
        // hapus semua kelas, arsip, dan import) sengaja hanya menyentuh tabel
        // masing-masing. Model ini memastikan tidak ada jalur kode yang bisa
        // menghapus akun admin, dimanapun perintah delete dipanggil.
        static::deleting(function (self $user) {
            if ($user->isProtectedAccount()) {
                throw new RuntimeException(
                    'Akun admin tidak dapat dihapus. Akun ini wajib dipertahankan '
                    .'agar aplikasi tidak terkunci tanpa akses.'
                );
            }
        });
    }

    /**
     * Apakah akun ini dilindungi dari penghapusan?
     */
    public function isProtectedAccount(): bool
    {
        return in_array($this->username, self::PROTECTED_USERNAMES, true);
    }

    /**
     * Apakah akun ini AKTIF dan diizinkan login?
     *
     * Nilai null/kosong dianggap Aktif agar kompatibel dengan akun lama
     * yang ada SEBELUM kolom users.status ditambahkan (migration
     * 2026_10_14_000001_add_status_to_users_table) - akun lama tidak boleh
     * tiba-tiba terkunci setelah update aplikasi.
     */
    public function isActive(): bool
    {
        $status = $this->attributes['status'] ?? null;

        return $status === null || $status === '' || $status === self::STATUS_AKTIF;
    }

    /**
     * Label tampilan role (Bahasa Indonesia) dari daftar role terpusat.
     */
    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    /**
     * Jumlah akun ADMIN yang masih AKTIF (dihitung dinamis, tanpa cache).
     * Dipakai penjaga "wajib tersisa minimal satu admin aktif" di
     * UserAccountController.
     */
    public static function countActiveAdmins(): int
    {
        return self::query()
            ->where('role', self::ROLE_ADMIN)
            ->where('status', self::STATUS_AKTIF)
            ->count();
    }


    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }
}

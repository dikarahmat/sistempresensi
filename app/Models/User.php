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
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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

    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }
}

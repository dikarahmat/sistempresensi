<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- 1. TAMBAHKAN IMPORT INI
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;
    use SoftDeletes; // <-- 2. AKTIFKAN TRAIT SOFTDELETES DI SINI!

    public ?string $qr_base64 = null;
    public ?string $photo_base64 = null;

    /**
     * Data siswa yang dipakai aplikasi disederhanakan: NISN (identitas),
     * Nama Lengkap, Kelas, dan Jenis Kelamin. Kolom KTP/GTK seperti tempat &
     * tanggal lahir, alamat, nama wali, dan nomor WhatsApp tidak lagi dipakai
     * di mana pun (form, import, laporan, maupun kartu), sehingga tidak
     * dihulkam lewat mass assignment.
     */
    protected $fillable = [
        'nisn',
        'name',
        'gender',
        'school_class_id',
        'status',
        'photo',
        'qr_token',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ... sisa kode ke bawah biarkan tetap sama seperti punya lu ...

    protected static function booted(): void
    {
        static::creating(function ($student) {
            if (empty($student->qr_token)) {
                $student->qr_token = (string) Str::uuid();
            }
            if (empty($student->status)) {
                $student->status = 'Aktif';
            }
        });

        static::saved(function () {
            \App\Services\DownloadCacheService::clearCardsCache();
        });

        static::restored(function () {
            \App\Services\DownloadCacheService::clearCardsCache();
        });

        static::deleting(function (Student $student) {
            // Hapus file foto dari storage jika ada
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }

            // Cascading delete ke seluruh data riwayat presensi siswa
            $student->attendances()->each(function (Attendance $attendance) {
                $attendance->delete();
            });

            \App\Services\DownloadCacheService::clearCardsCache();
        });
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    /**
     * Alias accessor untuk class_id -> school_class_id
     */
    public function getClassIdAttribute(): ?int
    {
        return $this->attributes['school_class_id'] ?? null;
    }

    public function setClassIdAttribute($value): void
    {
        $this->attributes['school_class_id'] = $value;
    }

    public function getKelasAttribute(): ?string
    {
        return $this->schoolClass?->name;
    }

    public function getNamaAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function setNamaAttribute($value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getJenisKelaminAttribute(): ?string
    {
        return $this->attributes['gender'] ?? null;
    }

    public function setJenisKelaminAttribute($value): void
    {
        $this->attributes['gender'] = $value;
    }
}

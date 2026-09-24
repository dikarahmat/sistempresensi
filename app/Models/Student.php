<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
    ];

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

        static::deleting(function (Student $student) {
            // Hapus file foto dari storage jika ada
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }

            // Cascading delete ke seluruh data riwayat presensi siswa
            $student->attendances()->each(function (Attendance $attendance) {
                $attendance->delete();
            });
        });
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    /**
     * Alias relasi class agar kompatibel saat dipanggil $student->class
     */
    public function class(): BelongsTo
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

    public function getNamaOrangTuaAttribute(): ?string
    {
        return $this->attributes['parent_name'] ?? null;
    }

    public function setNamaOrangTuaAttribute($value): void
    {
        $this->attributes['parent_name'] = $value;
    }

    public function getNamaWaliAttribute(): ?string
    {
        return $this->attributes['parent_name'] ?? null;
    }

    public function setNamaWaliAttribute($value): void
    {
        $this->attributes['parent_name'] = $value;
    }

    public function getNoHpWaliAttribute(): ?string
    {
        return $this->attributes['parent_phone'] ?? null;
    }

    public function setNoHpWaliAttribute($value): void
    {
        $this->attributes['parent_phone'] = $value;
    }
}
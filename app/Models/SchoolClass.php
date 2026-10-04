<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'grade', 'level', 'academic_year_id', 'teacher_id'];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Booted method untuk cascading delete anti-foreign key error dan default level.
     */
    protected static function booted(): void
    {
        static::creating(function (SchoolClass $schoolClass) {
            if (empty($schoolClass->level)) {
                $schoolClass->level = match((string) $schoolClass->grade) {
                    '7' => 'VII',
                    '8' => 'VIII',
                    '9' => 'IX',
                    default => 'VII',
                };
            }
        });

        static::saved(function () {
            \App\Services\DownloadCacheService::clearCardsCache();
        });

        static::deleting(function (SchoolClass $schoolClass) {
            // Cascade HANYA saat hapus PERMANEN (force delete).
            //
            // Soft delete (pindah ke Tempat Sampah) TIDAK boleh menyentuh data
            // siswa sama sekali. Halaman "Data Kelas" sudah menolak hapus
            // kelas yang masih punya siswa aktif, dan model Student punya
            // global scope SoftDeletes sehingga students() di sini hanya
            // menghitung siswa aktif.
            //
            // isForceDeleting() dipakai supaya students.school_class_id
            // (foreign key ke school_classes) tidak menyebabkan error saat
            // kelas benar-benar dihapus permanen dari Tempat Sampah.
            if ($schoolClass->isForceDeleting()) {
                // Gunakan each() agar event deleting pada model Student tetap terpanggil
                $schoolClass->students()->each(function (Student $student) {
                    $student->delete();
                });
            }

            \App\Services\DownloadCacheService::clearCardsCache();
        });
    }
}

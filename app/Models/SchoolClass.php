<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $fillable = ['name', 'grade', 'level', 'academic_year_id', 'teacher_id'];

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

        static::deleting(function (SchoolClass $schoolClass) {
            // Gunakan each() agar event deleting pada model Student tetap terpanggil
            $schoolClass->students()->each(function (Student $student) {
                $student->delete();
            });
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Teacher extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($teacher) {
            if (empty($teacher->qr_token)) {
                $teacher->qr_token = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::deleting(function ($teacher) {
            if ($teacher->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($teacher->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($teacher->photo);
            }
            \App\Models\SchoolClass::where('teacher_id', $teacher->id)->update(['teacher_id' => null]);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function schoolClass(): HasOne
    {
        return $this->hasOne(SchoolClass::class, 'teacher_id');
    }

    public function schoolClasses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    public function students(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Student::class, SchoolClass::class, 'teacher_id', 'school_class_id');
    }


    public function getPhoneNumberAttribute(): ?string
    {
        return $this->attributes['phone_number'] ?? $this->attributes['phone'] ?? null;
    }

    public function setPhoneNumberAttribute(?string $value): void
    {
        $this->attributes['phone_number'] = $value;
        $this->attributes['phone'] = $value;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value;
        $this->attributes['phone_number'] = $value;
    }
}
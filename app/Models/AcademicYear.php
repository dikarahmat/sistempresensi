<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'semester', 'start_date', 'end_date', 'is_active'];
    protected $casts = ['is_active' => 'boolean', 'start_date' => 'date', 'end_date' => 'date'];

    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public static function getActive(): ?self
    {
        return Cache::remember('academic_year_active', 3600, function () {
            return self::where('is_active', true)->first();
        });
    }

    public function makeActive(): void
    {
        self::query()->update(['is_active' => false]);
        $this->update(['is_active' => true]);
        self::clearActiveCache();
    }

    public static function clearActiveCache(): void
    {
        Cache::forget('academic_year_active');
    }

    protected static function booted(): void
    {
        static::updated(function ($model) {
            if ($model->is_active) {
                self::clearActiveCache();
            }
        });
    }
}
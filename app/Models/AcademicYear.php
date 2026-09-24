<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return self::where('is_active', true)->first();
    }

    public function makeActive(): void
    {
        self::query()->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }
}
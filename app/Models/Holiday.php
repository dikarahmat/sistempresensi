<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['date', 'start_date', 'end_date', 'description'];
    protected $casts = [
        'date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public static function isHoliday(string $date): bool
    {
        return self::where(function ($q) use ($date) {
            $q->where('date', $date)
              ->orWhere(function ($sub) use ($date) {
                  $sub->whereNotNull('start_date')
                      ->whereNotNull('end_date')
                      ->where('start_date', '<=', $date)
                      ->where('end_date', '>=', $date);
              });
        })->exists();
    }

    public static function getHolidayDescription(string $date): ?string
    {
        $holiday = self::where(function ($q) use ($date) {
            $q->where('date', $date)
              ->orWhere(function ($sub) use ($date) {
                  $sub->whereNotNull('start_date')
                      ->whereNotNull('end_date')
                      ->where('start_date', '<=', $date)
                      ->where('end_date', '>=', $date);
              });
        })->first();

        return $holiday ? $holiday->description : null;
    }
}
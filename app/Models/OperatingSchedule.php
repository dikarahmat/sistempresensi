<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OperatingSchedule extends Model
{
    public const DAY_NAMES = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
    ];

    protected $fillable = [
        'day_of_week',
        'check_in_time',
        'late_limit_time',
        'check_out_time',
        'check_out_limit_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    /**
     * Jadwal untuk tanggal tertentu. Null untuk Sabtu/Minggu karena
     * presensi hanya beroperasi Senin-Jumat.
     */
    public static function forDate(Carbon $date): ?self
    {
        $day = (int) $date->dayOfWeekIso;

        if (!array_key_exists($day, self::DAY_NAMES)) {
            return null;
        }

        return static::where('day_of_week', $day)->first();
    }

    /**
     * Empat jam operasional untuk tanggal tertentu.
     * Bila baris jadwal belum ada (mis. database lama), pakai nilai Setting
     * sebagai fallback agar presensi tetap berjalan.
     *
     * @return array{check_in_time: string, late_limit_time: string, check_out_time: string, check_out_limit_time: string}|null
     */
    public static function timesForDate(Carbon $date): ?array
    {
        $day = (int) $date->dayOfWeekIso;

        if (!array_key_exists($day, self::DAY_NAMES)) {
            return null;
        }

        $schedule = static::forDate($date);

        if ($schedule) {
            return [
                'check_in_time' => substr((string) $schedule->check_in_time, 0, 5),
                'late_limit_time' => substr((string) $schedule->late_limit_time, 0, 5),
                'check_out_time' => substr((string) $schedule->check_out_time, 0, 5),
                'check_out_limit_time' => substr((string) $schedule->check_out_limit_time, 0, 5),
            ];
        }

        $checkOut = Setting::getCheckOutTime();

        return [
            'check_in_time' => Setting::getCheckInTime(),
            'late_limit_time' => Setting::getLateLimitTime(),
            'check_out_time' => $checkOut,
            'check_out_limit_time' => date('H:i', strtotime($checkOut . ' +60 minutes')),
        ];
    }
}

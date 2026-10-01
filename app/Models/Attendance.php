<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STATUS_HADIR = 'Hadir';
    public const STATUS_TERLAMBAT = 'Terlambat';
    public const STATUS_SAKIT = 'Sakit';
    public const STATUS_IZIN = 'Izin';
    public const STATUS_ALFA = 'Alfa';

    public const REMARK_TEPAT_WAKTU = 'Tepat Waktu';
    public const REMARK_TERLAMBAT = 'Terlambat';

    protected $fillable = [
        'student_id', 'academic_year_id', 'date', 'check_in',
        'check_out', 'status', 'time_remark', 'is_late', 'late_minutes', 'notes', 'proof_document'
    ];

    protected $casts = [
        'date' => 'date',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
    ];

    /**
     * Penentu status tunggal presensi masuk (Single Source of Truth).
     * Aturan:
     * - Scan sampai Batas Terlambat (<=) = HADIR (Tepat Waktu)
     * - Scan setelah Batas Terlambat (>) = TERLAMBAT
     * Memakai zona waktu Asia/Jakarta dan perbandingan waktu yang presisi.
     */
    public static function evaluateCheckInTime(string|\Carbon\Carbon $checkIn, ?string $lateLimit = null): array
    {
        $timezone = 'Asia/Jakarta';
        $todayStr = \Carbon\Carbon::now($timezone)->toDateString();

        $lateLimitStr = $lateLimit ?: Setting::getLateLimitTime();
        if (strlen($lateLimitStr) === 5) {
            $lateLimitStr .= ':00';
        }

        $checkInCarbon = ($checkIn instanceof \Carbon\Carbon)
            ? $checkIn->copy()->setTimezone($timezone)
            : \Carbon\Carbon::parse($todayStr . ' ' . $checkIn, $timezone);

        $cutoffCarbon = \Carbon\Carbon::parse($todayStr . ' ' . $lateLimitStr, $timezone);

        $isLate = $checkInCarbon->greaterThan($cutoffCarbon);
        $lateMinutes = 0;

        if ($isLate) {
            $lateMinutes = (int) $cutoffCarbon->diffInMinutes($checkInCarbon);
            $displayStatus = self::STATUS_TERLAMBAT;
            $timeRemark = self::REMARK_TERLAMBAT;
            $displayRemark = "Terlambat (+{$lateMinutes} mnt)";
        } else {
            $displayStatus = self::STATUS_HADIR;
            $timeRemark = self::REMARK_TEPAT_WAKTU;
            $displayRemark = 'Tepat Waktu';
        }

        return [
            'db_status' => self::STATUS_HADIR,
            'status' => $displayStatus,
            'display_status' => $displayStatus,
            'time_remark' => $timeRemark,
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'display_remark' => $displayRemark,
        ];
    }

    /**
     * Dapatkan status efektif presensi untuk tampilan, rekap, & ekspor
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === self::STATUS_HADIR && ($this->time_remark === self::REMARK_TERLAMBAT || !empty($this->is_late))) {
            return self::STATUS_TERLAMBAT;
        }

        return $this->status ?: self::STATUS_HADIR;
    }

    protected static function booted(): void
    {
        static::saved(function () {
            \App\Services\DownloadCacheService::clearRekapCache();
            \Illuminate\Support\Facades\Cache::forget('dashboard_stats');
            \Illuminate\Support\Facades\Cache::forget('dashboard_today');
            \Illuminate\Support\Facades\Cache::forget('daily_attendance_summary');
        });

        static::deleted(function () {
            \App\Services\DownloadCacheService::clearRekapCache();
            \Illuminate\Support\Facades\Cache::forget('dashboard_stats');
            \Illuminate\Support\Facades\Cache::forget('dashboard_today');
            \Illuminate\Support\Facades\Cache::forget('daily_attendance_summary');
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}

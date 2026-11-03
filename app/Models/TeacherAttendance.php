<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Catatan kehadiran presensi guru (mirror Attendance untuk siswa).
 * Tidak ada tabel/model presensi guru sebelumnya, maka dibuat di sini.
 */
class TeacherAttendance extends Model
{
    public const STATUS_HADIR = 'Hadir';
    public const STATUS_TERLAMBAT = 'Terlambat';
    public const STATUS_SAKIT = 'Sakit';
    public const STATUS_IZIN = 'Izin';
    public const STATUS_ALFA = 'Alfa';
    public const STATUS_BELUM = 'Belum';

    public const VALID_STATUSES = [
        self::STATUS_HADIR,
        self::STATUS_TERLAMBAT,
        self::STATUS_SAKIT,
        self::STATUS_IZIN,
        self::STATUS_ALFA,
    ];

    public const REMARK_TEPAT_WAKTU = 'Tepat Waktu';
    public const REMARK_TERLAMBAT = 'Terlambat';

    protected $fillable = [
        'teacher_id', 'date', 'check_in',
        'check_out', 'status', 'time_remark', 'notes', 'proof_document'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Status efektif untuk tampilan: Hadir + terlambat -> Terlambat.
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === self::STATUS_HADIR && $this->time_remark === self::REMARK_TERLAMBAT) {
            return self::STATUS_TERLAMBAT;
        }

        return $this->status ?: self::STATUS_HADIR;
    }

    /**
     * Ringkasan status kehadiran seorang guru untuk satu rentang tanggal.
     * Aturan sama dengan Attendance::summarizeStudent.
     */
    public static function summarizeTeacher($attendances, int $schoolDays = 0, int $futureSchoolDays = 0): array
    {
        $hadir = 0;
        $terlambat = 0;
        $sakit = 0;
        $izin = 0;
        $alfaCatatan = 0;

        foreach ($attendances as $att) {
            switch ($att->effective_status) {
                case self::STATUS_HADIR:
                    $hadir++;
                    break;
                case self::STATUS_TERLAMBAT:
                    $terlambat++;
                    break;
                case self::STATUS_SAKIT:
                    $sakit++;
                    break;
                case self::STATUS_IZIN:
                    $izin++;
                    break;
                case self::STATUS_ALFA:
                    $alfaCatatan++;
                    break;
            }
        }

        return [
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'sakit' => $sakit,
            'izin' => $izin,
            'alfa' => $alfaCatatan,
        ];
    }
}

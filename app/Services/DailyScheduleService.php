<?php

namespace App\Services;

use App\Models\OperatingSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Facade;

/**
 * Service for reading operational schedules per day (Senin-Jumat).
 * Mengembalikan null untuk Sabtu/Minggu karena tidak ada presensi.
 *
 * @used-by ScannerController, AttendanceController, SettingController
 */
class DailyScheduleService
{
    /**
     * Jadwal untuk siswa pada tanggal tertentu.
     * Mengembalikan array dengan: check_in_time, late_limit_time, check_out_time, check_out_limit_time
     * atau null jika hari Sabtu/Minggu.
     *
     * @param Carbon $date
     * @return array|null
     */
    public static function forStudent(Carbon $date): ?array
    {
        return self::forTarget($date, 'siswa');
    }

    /**
     * Jadwal untuk guru pada tanggal tertentu.
     * Mengembalikan array dengan: check_in_time, late_limit_time, check_out_time, check_out_limit_time
     * atau null jika hari Sabtu/Minggu.
     * Guru tidak punya status terlambat, jadi late_limit_time boleh null/kosong.
     *
     * @param Carbon $date
     * @return array|null
     */
    public static function forTeacher(Carbon $date): ?array
    {
        return self::forTarget($date, 'guru');
    }

    /**
     * Jadwal untuk target tertentu (siswa/guru) pada tanggal tertentu.
     *
     * @param Carbon $date
     * @param string $target 'siswa' atau 'guru'
     * @return array|null
     */
    protected static function forTarget(Carbon $date, string $target): ?array
    {
        // Tidak ada presensi Sabtu/Minggu
        $dayOfWeek = (int) $date->dayOfWeekIso;

        if ($dayOfWeek >= 6) {
            // Sabtu = 6, Minggu = 7
            return null;
        }

        $schedule = OperatingSchedule::forDate($date);

        if ($schedule) {
            $result = [
                'check_in_time' => substr((string) $schedule->check_in_time, 0, 5),
                'late_limit_time' => substr((string) $schedule->late_limit_time, 0, 5),
                'check_out_time' => substr((string) $schedule->check_out_time, 0, 5),
                'check_out_limit_time' => substr((string) $schedule->check_out_limit_time, 0, 5),
            ];

            // Guru: late_limit_time bisa null/kosong (guru tidak punya status terlambat)
            if ($target === 'guru' && empty($result['late_limit_time'])) {
                $result['late_limit_time'] = null;
            }

            return $result;
        }

        // Fallback ke setting lama jika belum ada jadwal per hari
        $result = [
            'check_in_time' => OperatingSchedule::getStudentCheckInFallback(),
            'late_limit_time' => $target === 'guru' ? null : OperatingSchedule::getStudentLateLimitFallback(),
            'check_out_time' => OperatingSchedule::getCheckOutTimeFallback(),
            'check_out_limit_time' => date('H:i', strtotime(OperatingSchedule::getCheckOutTimeFallback() . ' +60 minutes')),
        ];

        return $result;
    }

    /**
     * Ambil jam masuk hari ini (guru/siswa).
     * Gunakan fungsi helper ini daripada langsung memanggil Setting.
     *
     * @param Carbon|null $date
     * @return string
     */
    public static function getCheckInTime(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $schedule = self::forStudent($date);

        if ($schedule) {
            return $schedule['check_in_time'];
        }

        return OperatingSchedule::getCheckInTime();
    }

    /**
     * Ambil batas keterlambatan hari ini (hanya untuk siswa).
     * Untuk guru kembalikan string kosong.
     *
     * @param Carbon|null $date
     * @return string
     */
    public static function getLateLimitTime(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $schedule = self::forStudent($date);

        if ($schedule && $schedule['late_limit_time']) {
            return $schedule['late_limit_time'];
        }

        // Untuk guru kembalikan kosong, atau untuk Sat/Minggu
        if ($date->dayOfWeekIso >= 6) {
            return '';
        }

        return OperatingSchedule::getLateLimitTime();
    }

    /**
     * Ambil jam pulang hari ini.
     *
     * @param Carbon|null $date
     * @return string
     */
    public static function getCheckOutTime(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $schedule = self::forStudent($date);

        if ($schedule) {
            return $schedule['check_out_time'];
        }

        return OperatingSchedule::getCheckOutTime();
    }

    /**
     * Ambil batas waktu pulang hari ini.
     *
     * @param Carbon|null $date
     * @return string
     */
    public static function getCheckOutLimitTime(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $schedule = self::forStudent($date);

        if ($schedule) {
            return $schedule['check_out_limit_time'];
        }

        return OperatingSchedule::getCheckOutLimitTime();
    }

    /**
     * Cek apakah hari ini adalah hari libur/akhir Pekan (tidak ada presensi).
     *
     * @param Carbon|null $date
     * @return bool
     */
    public static function isPresensiHariIni(?Carbon $date = null): bool
    {
        $date = $date ?? Carbon::now();
        return $date->dayOfWeekIso <= 5; // Senin(1) s.d Jumat(5)
    }
}
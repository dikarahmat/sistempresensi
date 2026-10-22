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

    /**
     * BELUM = siswa aktif yang belum punya catatan presensi pada tanggal itu.
     *
     * PENTING: BELUM BUKAN ALFA. Alfa hanya berlaku kalau Statusnya benar
     * -. dicatat (manual oleh guru). Ini yang dipakai seluruh halaman
     * presensi (daftar kelas, detail kelas, dashboard, kehadiran, rekap)
     * supaya semua angka saling cocok.
     */
    public const STATUS_BELUM = 'Belum';

    /**
     * LIBUR = hari libur (Pengaturan Hari Libur) atau akhir pekan.
     * Tidak dihitung sebagai Alfa.
     */
    public const STATUS_LIBUR = 'Libur';

    /**
     * Status yang boleh dipilih saat menyimpan presensi (kamus baku).
     *
     * 'Terlambat' disimpan di DB sebagai status 'Hadir' + penanda terlambat
     * (time_remark / is_late), jadi kolom status di database hanya berisi
     * Hadir / Sakit / Izin / Alfa.
     */
    public const VALID_STATUSES = [
        self::STATUS_HADIR,
        self::STATUS_TERLAMBAT,
        self::STATUS_SAKIT,
        self::STATUS_IZIN,
        self::STATUS_ALFA,
    ];

    public const REMARK_TEPAT_WAKTU = 'Tepat Waktu';
    public const REMARK_TERLAMBAT = 'Terlambat';

    /**
     * True bila tanggal tersebut hari libur (Pengaturan Hari Libur) atau
     * akhir pekan. Tanggal dihitung dalam zona WIB (Asia/Jakarta).
     */
    public static function isTanggalLibur(string $tanggal): bool
    {
        return Holiday::isHoliday($tanggal)
            || \Carbon\Carbon::parse($tanggal)->isWeekend();
    }

    /**
     * Status baku untuk satu siswa pada satu tanggal (satu sumber kebenaran).
     *
     * - Ada catatan  -> status efektif catatan (Hadir / Terlambat / Sakit /
     *                   Izin / Alfa).
     * - Tanpa catatan:
     *   - hari libur / akhir pekan -> LIBUR (tidak dihitung Alfa);
     *   - tanggal sudah lewat      -> ALFA (hitungan tampilan saja, TIDAK
     *                                 menulis ke database);
     *   - hari ini / masa depan    -> BELUM.
     */
    public static function statusOf(?self $attendance, ?string $tanggal = null, ?bool $isLibur = null): string
    {
        if ($attendance) {
            return $attendance->effective_status;
        }

        if ($tanggal === null) {
            return self::STATUS_BELUM;
        }

        $isLibur = $isLibur ?? self::isTanggalLibur($tanggal);

        if ($isLibur) {
            return self::STATUS_LIBUR;
        }

        return $tanggal < \Carbon\Carbon::now('Asia/Jakarta')->toDateString()
            ? self::STATUS_ALFA
            : self::STATUS_BELUM;
    }

    /**
     * Jumlah hari libur (Pengaturan Hari Libur) yang jatuh di dalam periode.
     *
     * Satu query untuk semua data hari libur, lalu tanggal-tanggalnya
     * dikumpulkan (termasuk hari libur ber rentang start_date..end_date).
     */
    public static function holidayDatesInPeriod(\Carbon\Carbon $mulai, \Carbon\Carbon $selesai): int
    {
        $tanggalLibur = [];

        foreach (Holiday::get() as $holiday) {
            if (! empty($holiday->date)) {
                $tanggalLibur[(string) $holiday->date] = true;
            }

            if (! empty($holiday->start_date) && ! empty($holiday->end_date)) {
                $cursor = \Carbon\Carbon::parse($holiday->start_date)->startOfDay();
                $end = \Carbon\Carbon::parse($holiday->end_date)->endOfDay();

                while ($cursor->lte($end)) {
                    $tanggalLibur[$cursor->toDateString()] = true;
                    $cursor->addDay();
                }
            }
        }

        if ($tanggalLibur === []) {
            return 0;
        }

        $jumlah = 0;
        $cursor = $mulai->copy()->startOfDay();

        while ($cursor->lte($selesai)) {
            if (isset($tanggalLibur[$cursor->toDateString()])) {
                $jumlah++;
            }
            $cursor->addDay();
        }

        return $jumlah;
    }

    /**
     * Jumlah HARI BELAJAR (bukan akhir pekan & bukan hari libur) dalam rentang.
     *
     * @param  \Carbon\Carbon  $mulai
     * @param  \Carbon\Carbon  $selesai
     * @param  bool  $sebelumHariIni  true = hanya menghitung hari yang sudah lewat
     */
    public static function schoolDaysCount(\Carbon\Carbon $mulai, \Carbon\Carbon $selesai, bool $sebelumHariIni = false): int
    {
        if ($mulai->gt($selesai)) {
            return 0;
        }

        $akhir = $selesai->copy()->endOfDay();
        $hariIni = \Carbon\Carbon::now('Asia/Jakarta')->endOfDay();

        if ($sebelumHariIni && $akhir->gt($hariIni)) {
            $akhir = $hariIni;
        }

        $jumlah = (int) $mulai->copy()->startOfDay()->diffInWeekdays($akhir);
        $jumlah = max(0, $jumlah - self::holidayDatesInPeriod($mulai->copy()->startOfDay(), $akhir));

        return $jumlah;
    }

    /**
     * Periode "TAHUN BERJALAN" yang dipakai BERSAMA oleh daftar Catatan
     * Kehadiran (/admin/kehadiran) dan mode "Semua" di Riwayat Presensi
     * (/admin/kehadiran/siswa/{id}/history), supaya angka keduanya pasti sama.
     *
     * Rentang: dari awal tahun ajaran aktif sampai HARI INI (WIB).
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon, 2: string} [mulai, selesai, label]
     */
    public static function activeYearPeriod(): array
    {
        $activeYear = AcademicYear::getActive();

        $mulai = $activeYear?->start_date
            ? \Carbon\Carbon::parse($activeYear->start_date)->startOfDay()
            : \Carbon\Carbon::now('Asia/Jakarta')->startOfYear();

        $selesai = \Carbon\Carbon::now('Asia/Jakarta')->endOfDay();

        if ($selesai->lt($mulai)) {
            $selesai = $mulai->copy();
        }

        $label = $activeYear
            ? ($activeYear->name . ' – ' . $activeYear->semester
                . ' (' . $mulai->translatedFormat('d M Y') . ' – ' . $selesai->translatedFormat('d M Y') . ')')
            : ($mulai->translatedFormat('d M Y') . ' – ' . $selesai->translatedFormat('d M Y'));

        return [$mulai, $selesai, $label];
    }

    /**
     * Periode "HARI INI" yang dipakai halaman daftar Catatan Kehadiran
     * (/admin/kehadiran) supaya angka kolom HADIR/TERLAMBAT/SAKIT/IZIN/ALFA/
     * BELUM adalah status HARI INI, bukan akumulasi sejumlah hari.
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon, 2: string} [mulai, selesai, label]
     */
    public static function activeYearPeriodToday(): array
    {
        $hariIni = \Carbon\Carbon::now('Asia/Jakarta');

        $mulai = $hariIni->copy()->startOfDay();
        $selesai = $hariIni->copy()->endOfDay();

        $label = $hariIni->translatedFormat('l, d M Y');

        return [$mulai, $selesai, $label];
    }

    /**
     * Ringkasan status presensi seorang siswa untuk satu rentang tanggal.
     *
     * SATU sumber untuk daftar Catatan Kehadiran (/admin/kehadiran) dan kartu
     * ringkasan Riwayat Presensi (/admin/kehadiran/siswa/{id}/history) supaya
     * angkanya tidak pernah berbeda antar halaman.
     *
     * Aturan (sama dengan DailyAttendanceSummary & halaman Presensi):
     *   hadir     = catatan 'Hadir' tepat waktu
     *   terlambat = catatan 'Hadir' yang telat (masih dihitung hadir)
     *   sakit/izin/alfa = catatan dengan status tersebut
     *   alfa      = alfa yang dicatat + hari belajar yang sudah lewat tanpa catatan
     *   belum     = hari belajar yang akan datang tanpa catatan
     *
     * @param  \Illuminate\Support\Collection  $attendances  catatan siswa pada rentang tsb
     * @param  int  $schoolDays  jumlah hari belajar yang sudah lewat dalam rentang
     * @param  int  $futureSchoolDays  jumlah hari belajar yang akan datang dalam rentang
     */
    public static function summarizeStudent($attendances, int $schoolDays = 0, int $futureSchoolDays = 0): array
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

        $hariAdaCatatan = $attendances->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->unique()
            ->count();

        return [
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'sakit' => $sakit,
            'izin' => $izin,
            'alfa' => $alfaCatatan + max(0, $schoolDays - $hariAdaCatatan),
            'belum' => max(0, $futureSchoolDays - $hariAdaCatatan),
        ];
    }

    /**
     * Daftar hari dalam satu periode lengkap dengan statusnya.
     *
     * Dipakai halaman Riwayat Presensi supaya tabelnya menampilkan SETIAP
     * hari pada periode itu: hari yang punya catatan (Hadir/Terlambat/Sakit/
     * Izin/Alfa), hari belajar tanpa catatan (Alfa untuk tanggal lampau, Belum
     * untuk hari ini/masa depan), serta hari libur & akhir pekan (Libur).
     *
     * @return array<int, array{date: string, is_libur: bool, status: string, attendance: ?self}>
     */
    public static function periodRows(string $startDate, string $endDate, $attendances = null): array
    {
        $mulai = \Carbon\Carbon::parse($startDate)->startOfDay();
        $selesai = \Carbon\Carbon::parse($endDate)->endOfDay();

        $catatanPerTanggal = collect($attendances ?? [])
            ->keyBy(fn ($att) => $att->date->toDateString());

        $rows = [];
        $cursor = $mulai->copy();

        while ($cursor->lte($selesai)) {
            $tanggal = $cursor->toDateString();
            $att = $catatanPerTanggal->get($tanggal);
            $isLibur = self::isTanggalLibur($tanggal);

            $rows[] = [
                'date' => $tanggal,
                'is_libur' => $isLibur,
                'status' => self::statusOf($att, $tanggal, $isLibur),
                'attendance' => $att,
            ];

            $cursor->addDay();
        }

        return $rows;
    }

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

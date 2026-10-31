<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
            return '-';
        }

        $isLibur = $isLibur ?? self::isTanggalLibur($tanggal);

        if ($isLibur) {
            return self::STATUS_LIBUR;
        }

        return '-';
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
            'alfa' => $alfaCatatan,
            'belum' => 0,
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
     * Dapatkan informasi jam masuk tampil (dengan zona WIB & placeholder "—" jika kosong).
     * Satu sumber: $this->check_in (kolom time di DB).
     */
    public function getCheckInDisplayAttribute(): string
    {
        if (empty($this->check_in)) {
            return '-';
        }

        try {
            $carbon = \Carbon\Carbon::parse($this->check_in)->setTimezone('Asia/Jakarta');
            return $carbon->format('H:i') . ' WIB';
        } catch (\Throwable $e) {
            return '-';
        }
    }

    /**
     * Get raw check_in value (wanjilu tanpa konversi timezone).
     */
    public function getRawCheckInAttribute(): ?string
    {
        return $this->check_in;
    }

    /**
     * Dapatkan menit keterlambatan (basis batas Pengaturan).
     * Hanya dihitung kalau status = Terlambat/is_late/time_remark = Terlambat
     * dan jam masuk ada. Paling sedikit 1 menit, sama dengan logika
     * Attendance::evaluateCheckInTime.
     */
    public function getLateMinutesAttribute(): int
    {
        if (!$this->exists) {
            return 0;
        }

        $isLate = ($this->time_remark === self::REMARK_TERLAMBAT || (bool) ($this->is_late))
            || $this->effective_status === self::STATUS_TERLAMBAT;

        if (!$isLate || empty($this->check_in)) {
            return 0;
        }

        try {
            $lateLimit = Setting::getLateLimitTime();
            $checkInCarbon = \Carbon\Carbon::parse($this->check_in)->setTimezone('Asia/Jakarta');
            $lateLimitCarbon = \Carbon\Carbon::parse($lateLimit, 'Asia/Jakarta');
            $diff = (int) $checkInCarbon->copy()->diffInMinutes($lateLimitCarbon, false);
            return max(1, abs($diff));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Dapatkan status efektif presensi untuk tampilan, rekap, & ekspor.
     * Aturan:
     * - Hadir + time_remark=Terlambat / is_late=true  -> Terlambat
     * - Selain itu -> status apa adanya (Hadir/Sakit/Izin/Alfa), atau HADIR bila kosong.
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === self::STATUS_HADIR && ($this->time_remark === self::REMARK_TERLAMBAT || !empty($this->is_late))) {
            return self::STATUS_TERLAMBAT;
        }

        return $this->status ?: self::STATUS_HADIR;
    }

    /**
     * Label keterlambatan: "Terlambat 15 menit" / "Terlambat 1 jam 5 menit"
     * / "Tepat Waktu" / "-".
     */
    public function getLateLabelAttribute(): string
    {
        if ($this->effective_status === self::STATUS_TERLAMBAT && $this->late_minutes > 0) {
            $m = $this->late_minutes;
            if ($m < 60) {
                return 'Terlambat ' . $m . ' menit';
            }
            $jam = intdiv($m, 60);
            $sisa = $m % 60;
            if ($sisa === 0) {
                return 'Terlambat ' . $jam . ' jam';
            }
            return 'Terlambat ' . $jam . ' jam ' . $sisa . ' menit';
        }

        if ($this->effective_status === self::STATUS_HADIR && $this->time_remark === self::REMARK_TEPAT_WAKTU) {
            return 'Tepat Waktu';
        }

        if (empty($this->check_in) || !in_array($this->effective_status, [self::STATUS_HADIR, self::STATUS_TERLAMBAT], true)) {
            return '-';
        }

        return '-';
    }

    /**
     * Data baris rekapan / PDF yang konsisten: jam masuk, keterlambatan, status,
     * catatan. Satu sumber dari satu Attendance + batas Pengaturan.
     */
    public static function dataRowForRekap($attendance, string $lateLimitTime): array
    {
        $att = $attendance;
        $status = self::STATUS_BELUM;
        $checkIn = null;
        $notes = '-';
        $proofDoc = null;
        $lateText = '-';
        $lateMinutes = 0;

        if ($att) {
            $status = $att->effective_status;
            $notes = $att->notes ?? '-';
            $proofDoc = $att->proof_document;
            $checkIn = $att->check_in;

            if ($status === self::STATUS_HADIR || $status === self::STATUS_TERLAMBAT) {
                $checkIn = $att->check_in ? substr($att->check_in, 0, 5) . ' WIB' : '-';

                if ($status === self::STATUS_TERLAMBAT || ($att->time_remark === self::REMARK_TERLAMBAT || (bool) ($att->is_late))) {
                    $diff = 0;
                    if ($checkIn !== '-' && $checkIn !== null && $checkIn !== '') {
                        try {
                            $diff = (int) \Carbon\Carbon::parse($checkIn)->diffInMinutes(\Carbon\Carbon::parse($lateLimitTime), false);
                        } catch (\Throwable $e) {
                            $diff = 0;
                        }
                    }
                    $lateMinutes = $diff < 0 ? (int) round(abs($diff)) : 0;
                    // Minimal 1 menit adalah aturan konsisten dengan status Terlambat.
                    if ($lateMinutes === 0) {
                        $lateMinutes = 1;
                    }
                    $lateText = rekap_format_late_minutes($lateMinutes);
                    $status = self::STATUS_TERLAMBAT;
                } else {
                    $lateText = 'Tepat Waktu';
                }
            }
        } else {
            /*
             * Tanpa catatan presensi:
             * - tanggal lampau (bukan hari libur/akhir pekan) -> ALFA
             *   (hitungan tampilan, tidak ditulis ke database);
             * - hari ini / masa depan -> BELUM;
             * - hari libur / akhir pekan -> LIBUR.
             * Sama dengan halaman Presensi (Attendance::statusOf).
             */
            $status = self::statusOf(null, null, false);
        }

        return [
            'student' => null,
            'check_in' => $checkIn,
            'late_text' => $lateText,
            'late_minutes' => $lateMinutes,
            'status' => $status,
            'notes' => $notes,
            'proof_document' => $proofDoc,
        ];
    }

    /**
     * Satu-satunya tempat menghitung baris jumlah siswa untuk presensi, kehadiran,
     * dan PDF. Hasilnya konsisten di semua halaman.
     */
    public static function mapStudentAttendanceToRow($attendance, string $tanggal, bool $isHoliday, string $lateLimitTime, $student = null): object
    {
        $att = $attendance;
        $timeRemark = null;
        $status = self::STATUS_BELUM;
        $lateMinutes = 0;
        $checkIn = null;
        $checkOut = null;
        $notes = null;
        $proofDocument = null;

        if ($att) {
            $timeRemark = $att->time_remark;
            $checkIn = $att->check_in;
            $checkOut = $att->check_out;
            $notes = $att->notes;
            $proofDocument = $att->proof_document;

            $status = $att->effective_status;

            // Status Terlambat (sama dengan logika evaluateCheckInTime) dan jam masuk ada.
            if ($status === self::STATUS_TERLAMBAT || (isset($att->is_late) && (bool) $att->is_late)) {
                $lateMinutes = (int) $att->late_minutes;
                if ($lateMinutes === 0 && $checkIn) {
                    $lateLimitCarbon = \Carbon\Carbon::parse($lateLimitTime, 'Asia/Jakarta');
                    $checkInCarbon = \Carbon\Carbon::parse($checkIn)->setTimezone('Asia/Jakarta');
                    $lateMinutes = (int) abs($checkInCarbon->diffInMinutes($lateLimitCarbon, false));
                    if ($lateMinutes === 0) {
                        $lateMinutes = 1;
                    }
                }
            }
        } else {
            // Tanpa catatan:
            // - hari libur/akhir pekan -> LIBUR
            // - tanggal lampau -> ALFA (hitungan tampilan saja, TIDAK ditulis ke DB)
            // - hari ini/masa depan   -> BELUM
            if ($isHoliday) {
                $status = self::STATUS_LIBUR;
            } elseif (Carbon::parse($tanggal)->startOfDay()->lt(Carbon::now('Asia/Jakarta')->startOfDay())) {
                $status = self::STATUS_ALFA;
            } else {
                $status = self::STATUS_BELUM;
            }
        }

        return (object) [
            'id' => $student->id ?? null,
            'name' => $student->name ?? '-',
            'nisn' => $student->nisn ?: '-',
            'gender' => $student->gender ?? 'L',
            'photo' => null,
            'jam_masuk' => $checkIn ? (substr($checkIn, 0, 5) . ' WIB') : '—',
            'jam_pulang' => $checkOut ? (substr($checkOut, 0, 5) . ' WIB') : '—',
            'raw_check_in' => $checkIn,
            'raw_check_out' => $checkOut,
            'current_status' => $status,
            'current_time_remark' => $timeRemark,
            'late_minutes' => $lateMinutes,
            'notes' => $notes,
            'proof_document' => $proofDocument,
            'attendance_id' => $att?->id,
            // AksesOR data dari model (satu sumber: tampil di Presensi, Kehadiran, PDF)
            'effective_status' => $att ? $att->effective_status : $status,
            'check_in_display' => $att ? $att->check_in_display : '—',
            'late_label' => $att ? $att->late_label : '–',
        ];
    }

    /**
     * Satu baris rekap harian yang konsisten (PDF + Excel + web).
     * Output: ['status','check_in','late_text','late_minutes','notes']
     * yang sama persis dengan RekapController::dataRowForRekap().
     */
    public static function makeDailyRow($attendance, string $date, string $lateLimit, string $dayStatus, string $studentClassId): array
    {
        $att = $attendance;
        $status = $dayStatus;
        $checkIn = '-';
        $lateText = '-';
        $notes = '-';

        if ($att) {
            $status = $att->effective_status;
            $notes = $att->notes ?? '-';

            if ($status === self::STATUS_HADIR || $status === self::STATUS_TERLAMBAT) {
                $checkIn = $att->check_in ? substr($att->check_in, 0, 5) . ' WIB' : '-';

                if ($status === self::STATUS_TERLAMBAT || ($att->time_remark === self::REMARK_TERLAMBAT || (bool) ($att->is_late))) {
                    $diff = 0;
                    if ($checkIn !== '-' && $checkIn !== null && $checkIn !== '') {
                        try {
                            $diff = (int) \Carbon\Carbon::parse($checkIn)->diffInMinutes(\Carbon\Carbon::parse($lateLimit), false);
                        } catch (\Throwable $e) {
                            $diff = 0;
                        }
                    }
                    $lateMinutes = $diff < 0 ? (int) round(abs($diff)) : 0;
                    if ($lateMinutes === 0) {
                        $lateMinutes = 1;
                    }
                    $lateText = rekap_format_late_minutes($lateMinutes);
                    $status = self::STATUS_TERLAMBAT;
                } else {
                    $lateText = 'Tepat Waktu';
                }
            }
        } else {
            /*
             * Tanpa catatan presensi:
             * - hari libur / akhir pekan -> LIBUR (bukan Alfa).
             * - selainnya -> status "-" (kosong, tidak ada catatan).
             * Sama dengan halaman Rekap & halaman Presensi.
             */
            $status = self::statusOf(null, $date, false) === self::STATUS_LIBUR ? self::STATUS_LIBUR : '-';
        }

        return [
            'status' => $status,
            'check_in' => $checkIn,
            'late_text' => $lateText,
            'late_minutes' => 0,
            'notes' => $notes,
            'class_id' => $studentClassId,
        ];
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

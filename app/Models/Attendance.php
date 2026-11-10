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
     * Kumpulan tanggal libur (Pengaturan Hari Libur) sebagai set ['Y-m-d' => true].
     * Mendukung libur satu tanggal (date) maupun rentang (start_date..end_date).
     * Satu query untuk semua data libur.
     */
    public static function holidayDateSet(): array
    {
        $set = [];

        foreach (Holiday::get() as $holiday) {
            if (! empty($holiday->date)) {
                $set[Carbon::parse($holiday->date)->toDateString()] = true;
            }

            if (! empty($holiday->start_date) && ! empty($holiday->end_date)) {
                $cursor = Carbon::parse($holiday->start_date)->startOfDay();
                $end = Carbon::parse($holiday->end_date)->startOfDay();

                while ($cursor->lte($end)) {
                    $set[$cursor->toDateString()] = true;
                    $cursor->addDay();
                }
            }
        }

        return $set;
    }

    /**
     * Jumlah hari libur HARI KERJA (Senin-Jumat) yang jatuh di dalam periode.
     * Libur yang jatuh di Sabtu/Minggu tidak dihitung lagi (sudah bukan hari sekolah).
     */
    public static function holidayDatesInPeriod(Carbon $mulai, Carbon $selesai): int
    {
        $set = self::holidayDateSet();
        if ($set === []) {
            return 0;
        }

        $jumlah = 0;
        $cursor = $mulai->copy()->startOfDay();
        $end = $selesai->copy()->startOfDay();

        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend() && isset($set[$cursor->toDateString()])) {
                $jumlah++;
            }
            $cursor->addDay();
        }

        return $jumlah;
    }

    /**
     * SATU-SATUNYA rumus hari sekolah: Senin-Jumat, bukan tanggal libur,
     * rentang INKLUSIF (tanggal mulai dan selesai ikut dihitung).
     * Dipakai oleh rekap siswa/guru, PDF, Excel, dan riwayat kehadiran.
     */
    public static function effectiveDays(Carbon $mulai, Carbon $selesai): int
    {
        $cursor = $mulai->copy()->startOfDay();
        $end = $selesai->copy()->startOfDay();

        if ($cursor->gt($end)) {
            return 0;
        }

        $set = self::holidayDateSet();
        $jumlah = 0;

        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend() && ! isset($set[$cursor->toDateString()])) {
                $jumlah++;
            }
            $cursor->addDay();
        }

        return $jumlah;
    }

    /**
     * Jumlah HARI BELAJAR (bukan akhir pekan & bukan hari libur) dalam rentang.
     *
     * @param  bool  $sebelumHariIni  true = hanya menghitung hari yang sudah lewat (sampai hari ini)
     */
    public static function schoolDaysCount(Carbon $mulai, Carbon $selesai, bool $sebelumHariIni = false): int
    {
        if ($mulai->copy()->startOfDay()->gt($selesai->copy()->startOfDay())) {
            return 0;
        }

        $akhir = $selesai->copy()->startOfDay();

        if ($sebelumHariIni) {
            $hariIni = Carbon::now('Asia/Jakarta')->startOfDay();
            if ($akhir->gt($hariIni)) {
                $akhir = $hariIni;
            }
        }

        return self::effectiveDays($mulai, $akhir);
    }

    /** Persentase kehadiran standar (dibatasi 0-100). Hadir sudah termasuk Terlambat. */
    public static function attendancePercentage(int $hadirTermasukTerlambat, int $hariEfektif): int
    {
        if ($hariEfektif <= 0) {
            return 0;
        }

        return (int) min(100, max(0, round(($hadirTermasukTerlambat / $hariEfektif) * 100)));
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
    public static function evaluateCheckInTime(string|\Carbon\Carbon $checkIn, ?string $lateLimit = null, ?string $openTime = null): array
    {
        $timezone = 'Asia/Jakarta';
        $now = \Carbon\Carbon::now($timezone);
        $todayStr = $now->toDateString();

        // Jam buka presensi: pakai jadwal PER HARI (Senin, Selasa, dst.) bila ada,
        // bukan setting tunggal lama (yang sebelumnya selalu terbaca jam Senin).
        $checkInTimeStr = $openTime;
        if (!$checkInTimeStr) {
            $daySchedule = OperatingSchedule::timesForDate($now);
            $checkInTimeStr = $daySchedule['check_in_time'] ?? null;
        }
        $checkInTimeStr = $checkInTimeStr ?: Setting::getCheckInTime(); // e.g. '06:00' atau '06:45'
        $openTimeLabel = substr((string) $checkInTimeStr, 0, 5);
        if (strlen($checkInTimeStr) === 5) {
            $checkInTimeStr .= ':00';
        }

        $lateLimitStr = $lateLimit ?: Setting::getLateLimitTime();
        if (strlen($lateLimitStr) === 5) {
            $lateLimitStr .= ':00';
        }

        $checkInCarbon = ($checkIn instanceof \Carbon\Carbon)
            ? $checkIn->copy()->setTimezone($timezone)
            : \Carbon\Carbon::parse($todayStr . ' ' . $checkIn, $timezone);
        
        $openingCarbon = \Carbon\Carbon::parse($todayStr . ' ' . $checkInTimeStr, $timezone);

        // Validasi ketat: Jika waktu saat ini / waktu check-in kurang dari jam buka (misal dini hari 00:24)
        if ($checkInCarbon->lessThan($openingCarbon)) {
            return [
                'db_status' => null,
                'status' => Attendance::STATUS_ALFA,
                'display_status' => Attendance::STATUS_ALFA,
                'time_remark' => 'Presensi belum dibuka. Presensi dimulai pukul ' . $openTimeLabel . '.',
                'is_late' => false,
                'late_minutes' => 0,
                'display_remark' => 'Presensi belum dibuka',
            ];
        }

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
     * Get raw check_in value.
     */
    public function getRawCheckInAttribute(): ?string
    {
        return $this->check_in;
    }

    /**
     * Dapatkan menit keterlambatan (basis batas Pengaturan).
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

    public static function dataRowForRekap($attendance, string $lateLimitTime): array
    {
        $att = $attendance;
        $status = self::STATUS_BELUM;
        $checkIn = null;
        $checkOut = '-';
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
                $checkOut = $att->check_out ? substr($att->check_out, 0, 5) . ' WIB' : '-';

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
            $status = self::statusOf(null, null, false);
        }

        return [
            'student' => null,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'late_text' => $lateText,
            'late_minutes' => $lateMinutes,
            'status' => $status,
            'notes' => $notes,
            'proof_document' => $proofDoc,
        ];
    }

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
            'effective_status' => $att ? $att->effective_status : $status,
            'check_in_display' => $att ? $att->check_in_display : '—',
            'late_label' => $att ? $att->late_label : '–',
        ];
    }

    public static function makeDailyRow($attendance, string $date, string $lateLimit, string $dayStatus, string $studentClassId): array
    {
        $att = $attendance;
        $status = $dayStatus;
        $checkIn = '-';
        $checkOut = '-';
        $lateText = '-';
        $notes = '-';

        if ($att) {
            $status = $att->effective_status;
            $notes = $att->notes ?? '-';

            if ($status === self::STATUS_HADIR || $status === self::STATUS_TERLAMBAT) {
                $checkIn = $att->check_in ? substr($att->check_in, 0, 5) . ' WIB' : '-';
                $checkOut = $att->check_out ? substr($att->check_out, 0, 5) . ' WIB' : '-';

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
            $status = self::statusOf(null, $date, false) === self::STATUS_LIBUR ? self::STATUS_LIBUR : '-';
        }

        return [
            'status' => $status,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
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
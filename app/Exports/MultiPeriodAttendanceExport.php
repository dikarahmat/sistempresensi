<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ekspor rekapitulasi presensi multi-periode (Harian / Mingguan / Bulanan).
 *
 * Struktur ekspor (SAMA dengan PDF, lihat pdf_multi_rekap.blade.php):
 *  - SATU SHEET PER KELAS. Nama sheet = nama kelas (mis. "7A").
 *  - Urutan kelas diambil DARI DATA: angka tingkat (grade) -> huruf rombel -> nama,
 *    tidak ada daftar kelas yang di-hardcode.
 *  - Nomor urut LANJUT antar kelas (tidak mulai dari 1 lagi di tiap kelas).
 *  - Kelas tanpa siswa DILEWATI.
 *  - Setiap sheet ditutup dengan legenda H/T/S/I/A/L dan blok tanda tangan
 *    3 kolom: Kepala Sekolah (Pengaturan) | Wali Kelas (Data Guru) | Petugas.
 *
 * Method statis buildSections(), buildHeading(), buildSubtitle() dipakai BERSAMA
 * oleh ekspor Excel (kelas ini) dan RekapController::exportPdf() supaya isi,
 * urutan, judul, dan penomoran kedua berkas identik.
 */
class MultiPeriodAttendanceExport implements FromArray, WithMultipleSheets, WithTitle, ShouldAutoSize, WithStyles, WithColumnWidths
{
    protected string $type;
    protected array $params;
    protected ?array $section;

    protected int $dataStartRow = 5;
    protected int $dataEndRow = 5;
    protected int $columnCount = 8;
    protected array $columnDataMax = [];
    protected int $legendRow = 0;
    protected int $signRow = 0;

    /** Nama pengganti bila tidak ada wali kelas / petugas (sama dengan template PDF). */
    public const NAME_PLACEHOLDER = '( ................................. )';

    public function __construct(string $type, array $params = [], ?array $section = null)
    {
        $this->type = in_array($type, ['harian', 'mingguan', 'bulanan']) ? $type : 'bulanan';
        $this->params = $params;
        $this->section = $section;
    }

    // =====================================================================
    // 1. SECTION PER KELAS (dipakai Excel DAN PDF)
    // =====================================================================

    /**
     * Kelompokkan baris rekap menjadi satu section per kelas, urut:
     * angka tingkat -> huruf rombel -> nama kelas.
     *
     * @param  array  $dataRows  baris rekap dari RekapController::getRecapData()
     *                           atau dari buildDataRows() ( ekspor Excel ).
     * @return array daftar section: class, className, rows (dengan 'no' lanjut),
     *               waliName, waliNip, heading, subtitle.
     */
    public static function buildSections(array $dataRows, string $type, array $params = [], ?int $classId = null): array
    {
        // Satu query untuk SELURUH kelas + seluruh wali kelas (eager load).
        $classes = SchoolClass::with('teacher')->get()->keyBy('id');

        $groups = [];
        foreach ($dataRows as $row) {
            $student = $row['student'] ?? null;
            if (!$student) {
                continue;
            }
            $classKey = $student->school_class_id ?? $row['class_id'] ?? null;
            $groups[$classKey ?? 'none'][] = $row;
        }

        $sections = [];
        foreach ($classes->sortBy(fn (SchoolClass $c) => self::classSortKey($c))->values() as $class) {
            if ($classId !== null && (int) $class->id !== $classId) {
                continue;
            }
            if (empty($groups[$class->id])) {
                // Kelas tanpa siswa tidak diekspor.
                continue;
            }
            $sections[] = self::makeSection($class, $groups[$class->id]);
        }

        // Siswa yang tidak terikat kelas mana pun (jarang, tetapi jangan hilang).
        if ($classId === null && !empty($groups['none'])) {
            $sections[] = self::makeSection(null, $groups['none']);
        }

        // Filter satu kelas tetapi kelas itu belum punya siswa: tetap tampil
        // satu sheet/section kosong supaya berkas tetap valid.
        if ($classId !== null && $sections === [] && isset($classes[$classId])) {
            $sections[] = self::makeSection($classes[$classId], []);
        }

        // Penomoran LANJUT antar kelas + judul & sub-judul per kelas.
        $no = 1;
        foreach ($sections as &$section) {
            foreach ($section['rows'] as &$row) {
                $row['no'] = $no++;
            }
            unset($row);
            $section['heading'] = self::buildHeading($type);
            $section['subtitle'] = self::buildSubtitle($type, $params, $section['className']);
        }
        unset($section);

        return $sections;
    }

    /**
     * Kunci pengurutan kelas dari data: angka tingkat lalu huruf rombel,
     * bukan daftar nama kelas yang ditulis manual.
     */
    public static function classSortKey(SchoolClass $class): string
    {
        $name = trim((string) $class->name);

        $grade = (int) ($class->grade ?? 0);
        if ($grade <= 0 && preg_match('/\d+/', $name, $m)) {
            $grade = (int) $m[0];
        }
        if ($grade <= 0) {
            $grade = 999; // data tanpa tingkat diletakkan di akhir
        }

        $withoutLeadingNumber = preg_replace('/^\s*\d+\s*/', '', $name);
        $letter = preg_match('/([A-Za-z]+)\s*$/', (string) $withoutLeadingNumber, $m)
            ? strtoupper($m[1])
            : '';

        return sprintf('%03d|%s|%s', $grade, $letter, $name);
    }

    protected static function makeSection(?SchoolClass $class, array $rows): array
    {
        $wali = $class?->teacher;

        return [
            'class' => $class,
            'className' => $class ? $class->name : '-',
            'rows' => $rows,
            'waliName' => $wali?->name,
            'waliNip' => $wali?->nip,
        ];
    }

    /** Judul laporan - sama persis di PDF dan Excel. */
    public static function buildHeading(string $type): string
    {
        Carbon::setLocale('id');
        $schoolName = Setting::getSchoolName();

        return 'LAPORAN REKAPITULASI PRESENSI ' . strtoupper($type) . ' - ' . ($schoolName ?: '-');
    }

    /** Sub-judul: periode | kelas | tahun ajaran - sama persis di PDF dan Excel. */
    public static function buildSubtitle(string $type, array $params, string $className): string
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');

        if ($type === 'harian') {
            $date = $params['date'] ?? $now->toDateString();
            $period = 'Tanggal: ' . Carbon::parse($date)->translatedFormat('l, d F Y');
        } elseif ($type === 'mingguan') {
            $startDate = $params['start_date'] ?? $now->copy()->startOfWeek()->toDateString();
            $endDate = $params['end_date'] ?? $now->copy()->startOfWeek()->addDays(4)->toDateString();
            $period = 'Periode: ' . Carbon::parse($startDate)->translatedFormat('d M Y')
                . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y');
        } else {
            $month = (int) ($params['month'] ?? $now->month);
            $year = (int) ($params['year'] ?? $now->year);
            $period = 'Bulan: ' . Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        }

        $activeYear = AcademicYear::getActive();

        return $period
            . ' | Kelas: ' . $className
            . ' | Tahun Ajaran: ' . ($activeYear ? $activeYear->name : '-');
    }

    /** Baris legenda - sama persis di PDF dan Excel. */
    public static function legendLines(): array
    {
        return [
            'KETERANGAN:',
            'H = Hadir, T = Terlambat, S = Sakit, I = Izin, A = Alfa, L = Libur',
            'Format Keterlambatan: kurang dari 60 menit ditulis "15 MNT", 60 menit atau lebih ditulis "1 JAM 5 MNT".',
        ];
    }

    /** Tiga posisi kolom tanda tangan: kiri, tengah, kanan. */
    public static function signatureColumns(int $columnCount): array
    {
        $columnCount = max(3, $columnCount);

        return [1, (int) ceil($columnCount / 2), $columnCount];
    }

    // =====================================================================
    // 2. MULTI SHEET
    // =====================================================================

    public function sheets(): array
    {
        $sections = self::buildSections(
            $this->buildDataRows(),
            $this->type,
            $this->params,
            $this->classId()
        );

        if ($sections === []) {
            // Tidak ada siswa sama sekali: tetap buat satu sheet agar file valid.
            $sections = [self::makeSection(null, [])];
            $sections[0]['heading'] = self::buildHeading($this->type);
            $sections[0]['subtitle'] = self::buildSubtitle($this->type, $this->params, $sections[0]['className']);
        }

        return array_map(
            fn (array $section) => new self($this->type, $this->params, $section),
            array_values($sections)
        );
    }

    /** Nama sheet = nama kelas. */
    public function title(): string
    {
        return (string) ($this->section()['className'] ?? '-');
    }

    // =====================================================================
    // 3. ISI SHEET
    // =====================================================================

    public function array(): array
    {
        Carbon::setLocale('id');

        $section = $this->section();
        $rows = $section['rows'] ?? [];
        $output = [];

        $output[] = [$section['heading'] ?? self::buildHeading($this->type)];
        $output[] = [$section['subtitle'] ?? self::buildSubtitle($this->type, $this->params, $section['className'] ?? '-')];
        // Baris kosong HARUS berisi minimal satu sel kosong (''), bukan [].
        // Maatwebsite Excel memakai flatMap(): baris [] dibuang sehingga semua
        // baris di bawahnya naik satu baris.
        $output[] = [''];

        $headerRow = [];
        $dataLines = [];

        if ($this->type === 'harian') {
            $headerRow = ['No', 'NIS', 'Nama Siswa', 'Kelas', 'JK', 'Jam Masuk', 'Keterlambatan', 'Status', 'Catatan'];

            foreach ($rows as $row) {
                $student = $row['student'];
                $dataLines[] = array_merge([$row['no'] ?? 0], [
                    $student->nis,
                    $student->name,
                    $student->schoolClass ? $student->schoolClass->name : '-',
                    $student->gender === 'Laki-laki' ? 'L' : 'P',
                    $row['check_in'],
                    $row['late_text'],
                    $row['status'],
                    $row['notes'],
                ]);
            }
        } elseif ($this->type === 'mingguan') {
            $dates = $this->periodDates();
            $totalEffective = count(array_filter(
                $dates,
                fn (Carbon $d) => !($d->isWeekend() || Holiday::isHoliday($d->toDateString()))
            ));

            $headerRow = ['No', 'NIS', 'Nama Siswa', 'Kelas', 'JK'];
            foreach ($dates as $d) {
                $headerRow[] = $d->translatedFormat('D, d/m');
            }
            $headerRow[] = 'H';
            $headerRow[] = 'T';
            $headerRow[] = 'S';
            $headerRow[] = 'I';
            $headerRow[] = 'A';
            $headerRow[] = '%';

            foreach ($rows as $row) {
                $student = $row['student'];
                $line = array_merge([$row['no'] ?? 0], [
                    $student->nis,
                    $student->name,
                    $student->schoolClass ? $student->schoolClass->name : '-',
                    $student->gender === 'Laki-laki' ? 'L' : 'P',
                ]);
                foreach ($dates as $d) {
                    $line[] = $row['days'][$d->toDateString()] ?? '-';
                }
                $percentage = $totalEffective > 0 ? round(($row['hadir'] / $totalEffective) * 100) : 0;
                $line[] = $row['hadir'];
                $line[] = $row['terlambat'];
                $line[] = $row['sakit'];
                $line[] = $row['izin'];
                $line[] = $row['alfa'];
                $line[] = $percentage . '%';
                $dataLines[] = $line;
            }
        } else {
            // BULANAN
            $month = (int) ($this->params['month'] ?? Carbon::now('Asia/Jakarta')->month);
            $year = (int) ($this->params['year'] ?? Carbon::now('Asia/Jakarta')->year);
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            $holidayCount = 0;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dDate = Carbon::createFromDate($year, $month, $d);
                if ($dDate->isWeekend() || Holiday::isHoliday($dDate->toDateString())) {
                    $holidayCount++;
                }
            }
            $totalEffective = max(1, $daysInMonth - $holidayCount);

            $headerRow = ['No', 'NIS', 'Nama Siswa', 'Kelas', 'JK'];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $headerRow[] = (string) $d;
            }
            $headerRow[] = 'H';
            $headerRow[] = 'T';
            $headerRow[] = 'S';
            $headerRow[] = 'I';
            $headerRow[] = 'A';
            $headerRow[] = '%';

            foreach ($rows as $row) {
                $student = $row['student'];
                $line = array_merge([$row['no'] ?? 0], [
                    $student->nis,
                    $student->name,
                    $student->schoolClass ? $student->schoolClass->name : '-',
                    $student->gender === 'Laki-laki' ? 'L' : 'P',
                ]);
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $line[] = $row['days'][$d] ?? '-';
                }
                $line[] = $row['hadir'];
                $line[] = $row['terlambat'];
                $line[] = $row['sakit'];
                $line[] = $row['izin'];
                $line[] = $row['alfa'];
                $line[] = round(($row['hadir'] / $totalEffective) * 100) . '%';
                $dataLines[] = $line;
            }
        }

        $output[] = $headerRow;
        foreach ($dataLines as $line) {
            $output[] = $line;
        }

        $this->columnCount = max(count($headerRow), 1);
        $this->dataEndRow = count($output);

        // Panjang teks per kolom HANYA dari baris judul tabel + baris data,
        // supaya blok tanda tangan (teks panjang) tidak memaksa lebar kolom.
        $this->columnDataMax = [];
        $this->rememberColumnLengths($headerRow);
        foreach ($dataLines as $line) {
            $this->rememberColumnLengths($line);
        }

        // ---- LEGENDA (per kelas / per sheet) ----
        $legend = self::legendLines();
        $output[] = [''];
        $this->legendRow = count($output) + 1;
        foreach ($legend as $line) {
            $output[] = [$line];
        }

        // ---- BLOK TANDA TANGAN 3 KOLOM (per kelas / per sheet) ----
        $output[] = [''];
        $this->signRow = count($output) + 1;
        foreach ($this->signatureRows($section) as $line) {
            $output[] = $line;
        }

        return $output;
    }

    /**
     * Baris tanda tangan: Kepala Sekolah | Wali Kelas | Petugas Presensi.
     * Baris & isi yang sama dengan bagian tandatangan di PDF.
     */
    protected function signatureRows(array $section): array
    {
        [$colLeft, $colMid, $colRight] = self::signatureColumns($this->columnCount);

        $blank = array_fill(0, $this->columnCount, '');

        $make = function (array $values) use ($blank) {
            $row = $blank;
            foreach ($values as $index1 => $value) {
                $row[$index1 - 1] = $value;
            }
            return $row;
        };

        $headmasterName = Setting::getHeadmasterName();
        $headmasterNip = Setting::getHeadmasterNip();
        $waliName = $section['waliName'] ?? null;
        $waliNip = $section['waliNip'] ?? null;

        $rows = [];
        // 1. Judul blok
        $rows[] = $make([
            $colLeft => 'Mengetahui,',
            $colMid => 'Wali Kelas',
            $colRight => 'Parung Panjang, ' . Carbon::now('Asia/Jakarta')->translatedFormat('d F Y'),
        ]);
        // 2. Jabatan
        $rows[] = $make([
            $colLeft => 'Kepala Sekolah',
            $colRight => 'Petugas Presensi',
        ]);
        // 3-4. Jarak tanda tangan
        $rows[] = $blank;
        $rows[] = $blank;
        // 5. Nama
        $rows[] = $make([
            $colLeft => $headmasterName ?: self::NAME_PLACEHOLDER,
            $colMid => $waliName ?: self::NAME_PLACEHOLDER,
            $colRight => self::NAME_PLACEHOLDER,
        ]);
        // 6. NIP
        $rows[] = $make([
            $colLeft => 'NIP. ' . ($headmasterNip ?: '-'),
            $colMid => 'NIP. ' . ($waliNip ?: '-'),
            $colRight => 'NIP. -',
        ]);

        return $rows;
    }

    protected function rememberColumnLengths(array $line): void
    {
        foreach ($line as $index => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $length = mb_strlen((string) $value);
            $column = $index + 1;
            if (!isset($this->columnDataMax[$column]) || $length > $this->columnDataMax[$column]) {
                $this->columnDataMax[$column] = $length;
            }
        }
    }

    // =====================================================================
    // 4. DATA (SELURUH SISWA SESUAI FILTER, BUKAN 100 BARIS PAGINASI)
    // =====================================================================

    protected function classId(): ?int
    {
        return isset($this->params['class_id']) && $this->params['class_id'] !== ''
            ? (int) $this->params['class_id']
            : null;
    }

    protected function students()
    {
        $query = Student::with('schoolClass');

        if (($classId = $this->classId()) !== null) {
            $query->where('school_class_id', $classId);
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /** Daftar tanggal pada periode mingguan. */
    protected function periodDates(): array
    {
        $startDate = $this->params['start_date'] ?? Carbon::now('Asia/Jakarta')->startOfWeek()->toDateString();
        $endDate = $this->params['end_date'] ?? Carbon::now('Asia/Jakarta')->startOfWeek()->addDays(4)->toDateString();

        $startObj = Carbon::parse($startDate);
        $endObj = Carbon::parse($endDate);

        $dates = [];
        $cur = $startObj->copy();
        while ($cur->lte($endObj)) {
            $dates[] = $cur->copy();
            $cur->addDay();
        }

        return $dates;
    }

    /**
     * Seluruh baris rekap untuk filter yang dipilih (semua siswa, bukan 100 baris).
     * Logika status/perhitungan sama dengan RekapController::getRecapData().
     */
    protected function buildDataRows(): array
    {
        Carbon::setLocale('id');

        $students = $this->students();
        $lateLimitTime = Setting::getLateLimitTime();
        $now = Carbon::now('Asia/Jakarta');
        $rows = [];

        if ($this->type === 'harian') {
            $date = $this->params['date'] ?? $now->toDateString();

            $attendances = Attendance::where('date', $date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');

            $isWeekend = Carbon::parse($date)->isWeekend();
            $isHoliday = Holiday::isHoliday($date) || $isWeekend;

            foreach ($students as $student) {
                $att = $attendances->get($student->id);
                $status = 'Belum Hadir';
                $checkIn = '-';
                $lateText = '-';
                $notes = '-';

                if ($att) {
                    $status = $att->status;
                    $notes = $att->notes ?? '-';
                    if ($status === 'Hadir') {
                        $checkIn = $att->check_in ? substr($att->check_in, 0, 5) . ' WIB' : '-';
                        if ($att->time_remark === 'Terlambat') {
                            // Cara menghitung selisih menit TIDAK diubah.
                            $diff = Carbon::parse($att->check_in)->diffInMinutes(Carbon::parse($lateLimitTime), false);
                            $lateMinutes = $diff < 0 ? (int) round(abs($diff)) : 0;
                            $lateText = rekap_format_late_minutes($lateMinutes);
                            $status = 'Terlambat';
                        } else {
                            $lateText = 'Tepat Waktu';
                        }
                    }
                } else {
                    if ($isHoliday) {
                        $status = 'Libur';
                    } elseif ($date < $now->toDateString()) {
                        $status = 'Alfa';
                    } elseif ($date === $now->toDateString() && $now->format('H:i') > $lateLimitTime) {
                        $status = 'Alfa';
                    }
                }

                $rows[] = [
                    'student' => $student,
                    'class_id' => $student->school_class_id,
                    'check_in' => $checkIn,
                    'late_text' => $lateText,
                    'status' => $status,
                    'notes' => $notes,
                ];
            }

            return $rows;
        }

        if ($this->type === 'mingguan') {
            $dates = $this->periodDates();
            $dateStrings = array_map(fn (Carbon $d) => $d->toDateString(), $dates);

            $attendances = Attendance::whereBetween('date', [reset($dateStrings), end($dateStrings)])
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->groupBy('student_id');

            foreach ($students as $student) {
                $stAtts = $attendances->get($student->id, collect())->keyBy('date');
                $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;
                $days = [];

                foreach ($dateStrings as $dStr) {
                    $att = $stAtts->get($dStr);
                    $code = '-';

                    if ($att) {
                        if ($att->status === 'Hadir') {
                            if ($att->time_remark === 'Terlambat') {
                                $code = 'T';
                                $t++;
                                $h++;
                            } else {
                                $code = 'H';
                                $h++;
                            }
                        } elseif ($att->status === 'Sakit') {
                            $code = 'S';
                            $s++;
                        } elseif ($att->status === 'Izin') {
                            $code = 'I';
                            $i++;
                        } elseif ($att->status === 'Alfa') {
                            $code = 'A';
                            $a++;
                        }
                    } else {
                        $dObj = Carbon::parse($dStr);
                        if ($dObj->isWeekend() || Holiday::isHoliday($dStr)) {
                            $code = 'L';
                        } elseif ($dStr <= $now->toDateString()) {
                            $code = 'A';
                            $a++;
                        }
                    }

                    $days[$dStr] = $code;
                }

                $rows[] = [
                    'student' => $student,
                    'class_id' => $student->school_class_id,
                    'days' => $days,
                    'hadir' => $h,
                    'terlambat' => $t,
                    'sakit' => $s,
                    'izin' => $i,
                    'alfa' => $a,
                ];
            }

            return $rows;
        }

        // BULANAN
        $month = (int) ($this->params['month'] ?? $now->month);
        $year = (int) ($this->params['year'] ?? $now->year);
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $startDate = Carbon::createFromDate($year, $month, 1)->toDateString();
        $endDate = Carbon::createFromDate($year, $month, $daysInMonth)->toDateString();

        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('student_id', $students->pluck('id'))
            ->select(['id', 'student_id', 'date', 'status', 'time_remark'])
            ->get()
            ->groupBy('student_id');

        $nowDateStr = $now->toDateString();

        foreach ($students as $student) {
            $stAtts = $attendances->get($student->id, collect())->keyBy(function ($item) {
                return (int) substr($item->date, 8, 2);
            });

            $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;
            $days = [];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $cDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $att = $stAtts->get($d);
                $code = '-';

                if ($att) {
                    if ($att->status === 'Hadir') {
                        if ($att->time_remark === 'Terlambat') {
                            $code = 'T';
                            $t++;
                            $h++;
                        } else {
                            $code = 'H';
                            $h++;
                        }
                    } elseif ($att->status === 'Sakit') {
                        $code = 'S';
                        $s++;
                    } elseif ($att->status === 'Izin') {
                        $code = 'I';
                        $i++;
                    } elseif ($att->status === 'Alfa') {
                        $code = 'A';
                        $a++;
                    }
                } else {
                    $dObj = Carbon::createFromDate($year, $month, $d);
                    if ($dObj->isWeekend() || Holiday::isHoliday($cDate)) {
                        $code = 'L';
                    } elseif ($cDate <= $nowDateStr) {
                        $code = 'A';
                        $a++;
                    }
                }

                $days[$d] = $code;
            }

            $rows[] = [
                'student' => $student,
                'class_id' => $student->school_class_id,
                'days' => $days,
                'hadir' => $h,
                'terlambat' => $t,
                'sakit' => $s,
                'izin' => $i,
                'alfa' => $a,
            ];
        }

        return $rows;
    }

    protected function section(): array
    {
        if ($this->section === null) {
            $this->section = [
                'class' => null,
                'className' => '-',
                'rows' => [],
                'waliName' => null,
                'waliNip' => null,
                'heading' => self::buildHeading($this->type),
                'subtitle' => self::buildSubtitle($this->type, $this->params, '-'),
            ];
        }

        return $this->section;
    }

    // =====================================================================
    // 5. FORMAT
    // =====================================================================

    public function styles(Worksheet $sheet): array
    {
        $lastCol = Coordinate::stringFromColumnIndex($this->columnCount);

        // Judul & sub-judul (digabung agar tidak ikut mempengaruhi auto-size)
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('1D4ED8'));
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new Color('475569'));

        // Baris judul tabel
        $tableHeaderRange = "A4:{$lastCol}4";
        $sheet->getStyle($tableHeaderRange)->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $sheet->getStyle($tableHeaderRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('2563EB');
        $sheet->getStyle($tableHeaderRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Garis + perataan data
        if ($this->dataEndRow >= $this->dataStartRow) {
            $dataRange = "A4:{$lastCol}{$this->dataEndRow}";
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('CBD5E1');
            $sheet->getStyle("A5:A{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B5:B{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D5:E{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Kolom JK (E): L biru cerah, P merah cerah (tidak bold).
            $this->styleJkColumn($sheet);
        }

        // Legenda
        if ($this->legendRow > 0) {
            $sheet->getStyle("A{$this->legendRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$this->legendRow}:{$lastCol}" . ($this->legendRow + 2))
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        // Blok tanda tangan: teks rata kiri, nama & jabatan ditebalkan
        if ($this->signRow > 0) {
            $signStart = $this->signRow;
            $signEnd = $signStart + 5;
            [$colLeft, $colMid, $colRight] = self::signatureColumns($this->columnCount);

            $leftLetter = Coordinate::stringFromColumnIndex($colLeft);
            $midLetter = Coordinate::stringFromColumnIndex($colMid);
            $rightLetter = Coordinate::stringFromColumnIndex($colRight);

            // Baris judul (1) dan nama (5)
            foreach ([$signStart, $signStart + 4] as $rowIndex) {
                foreach ([$leftLetter, $midLetter, $rightLetter] as $letter) {
                    $sheet->getStyle($letter . $rowIndex)->getFont()->setBold(true);
                }
            }

            // Rata kiri untuk kolom kiri & tengah, rata kanan untuk kolom kanan
            $sheet->getStyle($leftLetter . $signStart . ':' . $leftLetter . $signEnd)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle($midLetter . $signStart . ':' . $midLetter . $signEnd)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle($rightLetter . $signStart . ':' . $rightLetter . $signEnd)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        return [];
    }

    /**
     * Warna huruf kolom JK (kolom E) di Excel:
     * L = biru cerah (#3B82F6), P = merah cerah (#EF4444) — PERSIS sama
     * dengan kolom JK di halaman web Rekap (lihat .rekap-jk di
     * admin/attendances/rekap.blade.php).
     *
     * HANYA warna yang diubah: huruf dibiarkan TIDAK bold dan ukuran font
     * tetap default sheet, supaya tidak menonjol dibanding kolom lain.
     * Nilai sel dibaca dari sheet yang sudah terisi, jadi tidak ada data,
     * urutan, maupun perhitungan yang berubah.
     * Berlaku untuk Harian, Mingguan, dan Bulanan (kolom JK selalu kolom E).
     */
    protected function styleJkColumn(Worksheet $sheet): void
    {
        for ($row = $this->dataStartRow; $row <= $this->dataEndRow; $row++) {
            $value = $sheet->getCell('E' . $row)->getValue();
            $jk = is_scalar($value) ? strtoupper(trim((string) $value)) : '';

            if ($jk !== 'L' && $jk !== 'P') {
                continue;
            }

            // Jangan setBold()/setSize(): sel tetap memakai gaya default
            // (tidak bold, ukuran 11) seperti kolom data lainnya.
            $sheet->getStyle('E' . $row)->getFont()
                ->setColor(new Color($jk === 'L' ? '3B82F6' : 'EF4444'));
        }
    }

    /**
     * Hanya tiga kolom yang menyimpan teks tanda tangan yang lebar-nya dikunci
     * berdasarkan isi tabel, sehingga auto-size tidak melebarkan kolom "No"/
     * "JK"/kolom hari hanya karena teks tanda tangan.
     */
    public function columnWidths(): array
    {
        $widths = [];

        foreach (self::signatureColumns($this->columnCount) as $column) {
            $length = $this->columnDataMax[$column] ?? 0;
            $letter = Coordinate::stringFromColumnIndex($column);
            $widths[$letter] = (float) min(45, max(6, $length + 3));
        }

        return $widths;
    }
}

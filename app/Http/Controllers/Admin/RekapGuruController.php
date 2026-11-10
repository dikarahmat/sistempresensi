<?php

namespace App\Http\Controllers\Admin;

use App\Exports\MultiPeriodAttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Holiday;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapGuruController extends Controller
{
    private const TZ = 'Asia/Jakarta';

    public function index(Request $request): View
    {
        // Halaman hanya punya tab Harian/Mingguan/Bulanan; semester & tahunan hanya untuk cetak.
        if (in_array($request->query('type'), ['semester', 'tahunan'], true)) {
            $request->query->set('type', 'bulanan');
        }

        $ctx  = $this->context($request);
        $data = $this->buildData($ctx);

        return view('admin.attendances.rekap-guru', array_merge($data, [
            'type'      => $ctx['type'],
            'date'      => $ctx['start']->toDateString(),
            'startDate' => $ctx['start']->toDateString(),
            'endDate'   => $ctx['end']->toDateString(),
            'month'     => $ctx['month'],
            'year'      => $ctx['year'],
            // Data modal "Cetak Rekap Presensi Guru"
            'popupDate'     => Carbon::now(self::TZ)->toDateString(),
            'popupMonth'    => $ctx['month'],
            'popupYear'     => $ctx['year'],
            'academicYears' => AcademicYear::orderBy('start_date', 'desc')->get(),
            'activeYear'    => AcademicYear::getActive(),
        ]));
    }

    /**
     * Cetak lewat modal (POST). Mengubah isian modal menjadi parameter yang
     * dipahami exportPdf / exportExcel, lalu memakai ulang keduanya.
     */
    public function printRekap(Request $request)
    {
        $data = $request->validate([
            'format'               => 'required|in:pdf,excel',
            'period_type'          => 'required|in:harian,mingguan,bulanan,semester,tahunan',
            'period_date'          => 'nullable|date',
            'period_month'         => 'nullable|integer|between:1,12',
            'period_year'          => 'nullable|integer|min:2000|max:2100',
            'period_semester'      => 'nullable|in:ganjil,genap',
            'period_academic_year' => 'nullable|integer|exists:academic_years,id',
        ]);

        $tgl    = Carbon::parse($data['period_date'] ?? Carbon::now(self::TZ)->toDateString(), self::TZ);
        $params = ['type' => $data['period_type']];

        switch ($data['period_type']) {
            case 'harian':
                $params['date'] = $tgl->toDateString();
                break;
            case 'mingguan':
                // Senin - Minggu dari tanggal yang dipilih
                $params['start_date'] = $tgl->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                $params['end_date']   = $tgl->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
                break;
            case 'bulanan':
                $params['month'] = $data['period_month'] ?? $tgl->month;
                $params['year']  = $data['period_year'] ?? $tgl->year;
                break;
            default: // semester | tahunan
                $params['period_semester']      = $data['period_semester'] ?? 'ganjil';
                $params['period_academic_year'] = $data['period_academic_year'] ?? null;
        }

        $sub = Request::create('', 'GET', array_filter($params, fn ($v) => $v !== null));

        return $data['format'] === 'excel'
            ? $this->exportExcel($sub)
            : $this->exportPdf($sub);
    }

    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        Carbon::setLocale('id');

        $ctx  = $this->context($request);
        $data = $this->buildData($ctx);

        $schoolName  = Setting::getSchoolName();
        $tahunAjaran = AcademicYear::getActive()?->name ?? '-';

        if ($ctx['type'] === 'harian') {
            $periodText = 'Tanggal: ' . $ctx['start']->locale('id')->translatedFormat('l, d F Y');
        } elseif ($ctx['type'] === 'mingguan') {
            $periodText = 'Periode: ' . $ctx['start']->locale('id')->translatedFormat('d M Y')
                . ' s/d ' . $ctx['end']->locale('id')->translatedFormat('d M Y');
        } elseif ($ctx['type'] === 'bulanan') {
            $periodText = 'Bulan: ' . $ctx['start']->locale('id')->translatedFormat('F Y');
        } else {
            $periodText = 'Periode: ' . $ctx['start']->locale('id')->translatedFormat('F Y')
                . ' s/d ' . $ctx['end']->locale('id')->translatedFormat('F Y');
        }

        if ($ctx['type'] === 'semester') {
            $heading = 'LAPORAN REKAPITULASI PRESENSI GURU SEMESTER ' . strtoupper($ctx['semester'] ?? 'ganjil');
        } elseif ($ctx['type'] === 'tahunan') {
            $heading = 'LAPORAN REKAPITULASI PRESENSI GURU TAHUNAN';
        } else {
            $heading = 'LAPORAN REKAPITULASI PRESENSI GURU ' . strtoupper($ctx['type']) . ' - ' . ($schoolName ?: '-');
        }

        $pdf = Pdf::loadView('admin.attendances.pdf_rekap_guru', array_merge($data, [
            'type'           => $ctx['type'],
            'date'           => $ctx['start']->toDateString(),
            'periodLabel'    => $ctx['label'],
            'heading'        => $heading,
            'subtitle'       => $periodText . ' | Tahun Ajaran: ' . $tahunAjaran,
            'title'          => 'Rekap Presensi Guru ' . $ctx['type'],
            'schoolName'     => $schoolName,
            'schoolAddress'  => Setting::getSchoolAddress(),
            'activeYear'     => AcademicYear::getActive(),
            'headmasterName' => Setting::getHeadmasterName(),
            'headmasterNip'  => Setting::getHeadmasterNip(),
            'printedAt'      => Carbon::now(self::TZ)->locale('id')->translatedFormat('d F Y, H:i'),
            'signedAt'       => Carbon::now(self::TZ)->locale('id')->translatedFormat('d F Y'),
        ]))->setPaper('a4', 'landscape');

        return $pdf->download('rekap-presensi-guru-' . $ctx['type'] . '-' . $ctx['start']->toDateString() . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $ctx  = $this->context($request);
        $data = $this->buildData($ctx);

        if ($ctx['type'] === 'harian') {
            $headings = ['NO', 'NIP', 'NAMA GURU', 'JK', 'JAM MASUK', 'STATUS'];
            $out = [];
            foreach ($data['dataRows'] as $i => $r) {
                $out[] = [$i + 1, (string) $r['nip'], strtoupper($r['name']), $r['jk'], $r['check_in'], strtoupper($r['status'])];
            }
        } elseif (in_array($ctx['type'], ['semester', 'tahunan'], true)) {
            $headings = ['NO', 'NIP', 'NAMA GURU', 'JK'];
            foreach ($data['months'] as $m) {
                $headings[] = $m['label'] . ' ' . $m['year'];
            }
            array_push($headings, 'H', 'S', 'I', 'A', '%');
            $out = [];
            foreach ($data['dataRows'] as $i => $r) {
                $row = [$i + 1, (string) $r['nip'], strtoupper($r['name']), $r['jk']];
                foreach ($data['months'] as $m) {
                    $row[] = $r['monthly'][$m['key']] ?? '-';
                }
                array_push($row, $r['hadir'], $r['sakit'], $r['izin'], $r['alfa'], $r['percentage'] . '%');
                $out[] = $row;
            }
        } else {
            $headings = ['NO', 'NIP', 'NAMA GURU', 'JK'];
            foreach ($data['dateColumns'] as $c) {
                $headings[] = $c['label'];
            }
            array_push($headings, 'H', 'S', 'I', 'A', '%');
            $out = [];
            foreach ($data['dataRows'] as $i => $r) {
                $row = [$i + 1, (string) $r['nip'], strtoupper($r['name']), $r['jk']];
                foreach ($data['dateColumns'] as $c) {
                    $row[] = $r['days'][$c['date']] ?? '-';
                }
                array_push($row, $r['hadir'], $r['sakit'], $r['izin'], $r['alfa'], $r['percentage'] . '%');
                $out[] = $row;
            }
        }

        [$heading, $subtitle] = $this->headingAndSubtitle($ctx);

        // Export Excel guru dibuat langsung di sini (class anonim) supaya tidak bergantung pada
        // file/autoloader terpisah.
        $export = new class($headings, $out, $heading, $subtitle) extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithStyles, WithColumnWidths, WithCustomValueBinder
        {
    public const NAME_PLACEHOLDER = '( ................................. )';

    protected int $dataStartRow = 5;
    protected int $dataEndRow = 5;
    protected int $legendRow = 0;
    protected int $signRow = 0;
    protected int $columnCount;
    protected bool $isHarian = true;
    protected int $signColumn = 6;

    public function __construct(
        protected array $headings,
        protected array $rows,
        protected string $heading,
        protected string $subtitle
    ) {
        $this->columnCount = max(count($headings), 3);
        $this->isHarian = ($headings[4] ?? '') === 'JAM MASUK';
        // Kolom tanda tangan: harian di kolom terakhir; mingguan/bulanan dll. digeser ke kiri
        // karena kolom-kolomnya sempit (teks tanda tangan meluap ke sel kosong di kanannya).
        $this->signColumn = $this->isHarian ? $this->columnCount : max(3, $this->columnCount - 7);
    }

    /** NIP (kolom B) selalu teks; sel lain mengikuti perilaku bawaan. */
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getColumn() === 'B' && $cell->getRow() >= $this->dataStartRow && is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function array(): array
    {
        Carbon::setLocale('id');

        $out = [];
        $out[] = [$this->heading];
        $out[] = [$this->subtitle];
        $out[] = [''];
        $out[] = $this->headings;

        foreach ($this->rows as $row) {
            $row[1] = ($row[1] ?? '') !== '' && $row[1] !== null ? (string) $row[1] : '-';
            $out[] = $row;
        }
        $this->dataEndRow = count($out);

        // Legenda
        $out[] = [''];
        $this->legendRow = count($out) + 1;
        foreach (MultiPeriodAttendanceExport::legendLines(false) as $line) {
            $out[] = [$line];
        }

        // Tanda tangan: kiri Kepala Sekolah, kanan petugas
        $out[] = [''];
        $this->signRow = count($out) + 1;
        foreach ($this->signatureRows() as $line) {
            $out[] = $line;
        }

        return $out;
    }

    protected function signatureRows(): array
    {
        $right = $this->signColumn;
        $blank = array_fill(0, $this->columnCount, '');
        $make = function (array $values) use ($blank) {
            $row = $blank;
            foreach ($values as $idx => $value) {
                $row[$idx - 1] = $value;
            }

            return $row;
        };

        $headmasterName = Setting::getHeadmasterName();
        $headmasterNip = Setting::getHeadmasterNip();

        // Sama dengan PDF guru: guru tidak punya wali kelas, jadi hanya
        // tanda tangan Kepala Sekolah di sisi kanan.
        return [
            $make([$right => 'Parung Panjang, ' . Carbon::now('Asia/Jakarta')->translatedFormat('d F Y')]),
            $make([$right => 'Kepala Sekolah']),
            $blank,
            $blank,
            $make([$right => $headmasterName ?: self::NAME_PLACEHOLDER]),
            $make([$right => 'NIP. ' . ($headmasterNip ?: '-')]),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = Coordinate::stringFromColumnIndex($this->columnCount);

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('1D4ED8'));
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new Color('475569'));

        $header = "A4:{$lastCol}4";
        $sheet->getStyle($header)->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $sheet->getStyle($header)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('2563EB');
        $sheet->getStyle($header)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        if ($this->dataEndRow >= $this->dataStartRow) {
            $sheet->getStyle("A4:{$lastCol}{$this->dataEndRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('CBD5E1');
            $sheet->getStyle("A5:B{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D5:{$lastCol}{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // JK (kolom D): L biru, P merah
            for ($r = $this->dataStartRow; $r <= $this->dataEndRow; $r++) {
                $jk = strtoupper(trim((string) $sheet->getCell('D' . $r)->getValue()));
                if ($jk === 'L' || $jk === 'P') {
                    $sheet->getStyle('D' . $r)->getFont()->setColor(new Color($jk === 'L' ? '3B82F6' : 'EF4444'));
                }
            }
        }

        if ($this->legendRow > 0) {
            $sheet->getStyle("A{$this->legendRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$this->legendRow}:{$lastCol}" . ($this->legendRow + 3))
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        if ($this->signRow > 0) {
            $start = $this->signRow;
            $signLetter = Coordinate::stringFromColumnIndex($this->signColumn);
            $end = $start + 5;
            foreach ([$start + 1, $start + 4] as $r) {
                $sheet->getStyle($signLetter . $r)->getFont()->setBold(true);
            }
            $sheet->getStyle("{$signLetter}{$start}:{$signLetter}{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        return [];
    }

    public function columnWidths(): array
    {
        // Semua lebar kolom dikunci supaya teks panjang (judul, keterangan, tanda tangan)
        // tidak memaksa kolom No jadi lebar.
        $widths = ['A' => 6.0, 'B' => 24.0, 'C' => 38.0, 'D' => 6.0];

        for ($i = 5; $i <= $this->columnCount; $i++) {
            $label = (string) ($this->headings[$i - 1] ?? '');
            $widths[Coordinate::stringFromColumnIndex($i)] = (float) max(5, min(14, mb_strlen($label) + 3));
        }

        return $widths;
    }

        };

        return Excel::download($export, 'rekap-presensi-guru-' . $ctx['type'] . '-' . $ctx['start']->toDateString() . '.xlsx');
    }

    /** Judul & sub-judul laporan (dipakai Excel; sama dengan versi PDF). */
    private function headingAndSubtitle(array $ctx): array
    {
        Carbon::setLocale('id');
        $schoolName  = Setting::getSchoolName();
        $tahunAjaran = AcademicYear::getActive()?->name ?? '-';

        if ($ctx['type'] === 'harian') {
            $periodText = 'Tanggal: ' . $ctx['start']->locale('id')->translatedFormat('l, d F Y');
        } elseif ($ctx['type'] === 'mingguan') {
            $periodText = 'Periode: ' . $ctx['start']->locale('id')->translatedFormat('d M Y')
                . ' s/d ' . $ctx['end']->locale('id')->translatedFormat('d M Y');
        } elseif ($ctx['type'] === 'bulanan') {
            $periodText = 'Bulan: ' . $ctx['start']->locale('id')->translatedFormat('F Y');
        } else {
            $periodText = 'Periode: ' . $ctx['start']->locale('id')->translatedFormat('F Y')
                . ' s/d ' . $ctx['end']->locale('id')->translatedFormat('F Y');
        }

        if ($ctx['type'] === 'semester') {
            $heading = 'LAPORAN REKAPITULASI PRESENSI GURU SEMESTER ' . strtoupper($ctx['semester'] ?? 'ganjil');
        } elseif ($ctx['type'] === 'tahunan') {
            $heading = 'LAPORAN REKAPITULASI PRESENSI GURU TAHUNAN';
        } else {
            $heading = 'LAPORAN REKAPITULASI PRESENSI GURU ' . strtoupper($ctx['type']) . ' - ' . ($schoolName ?: '-');
        }

        return [$heading, $periodText . ' | Tahun Ajaran: ' . $tahunAjaran];
    }

    private function context(Request $request): array
    {
        $now  = Carbon::now(self::TZ);
        $type = $request->query('type', $request->query('period', 'harian'));
        $type = in_array($type, ['harian', 'mingguan', 'bulanan', 'semester', 'tahunan'], true) ? $type : 'harian';

        $month = max(1, min(12, (int) $request->query('month', $now->month)));
        $year  = (int) $request->query('year', $now->year);
        $semester = null;

        if ($type === 'harian') {
            $start = Carbon::parse($request->query('date', $now->toDateString()), self::TZ)->startOfDay();
            $end   = $start->copy()->endOfDay();
            $label = 'HARIAN ' . $start->locale('id')->translatedFormat('d F Y');
        } elseif ($type === 'mingguan') {
            $start = $request->filled('start_date')
                ? Carbon::parse($request->query('start_date'), self::TZ)->startOfDay()
                : $now->copy()->startOfWeek()->startOfDay();
            $end = $request->filled('end_date')
                ? Carbon::parse($request->query('end_date'), self::TZ)->endOfDay()
                : $start->copy()->addDays(6)->endOfDay();
            if ($end->lt($start)) {
                $end = $start->copy()->endOfDay();
            }
            if ($start->diffInDays($end) > 31) {
                $end = $start->copy()->addDays(30)->endOfDay();
            }
            $label = 'MINGGUAN ' . $start->locale('id')->translatedFormat('d F Y') . ' - ' . $end->locale('id')->translatedFormat('d F Y');
        } elseif ($type === 'bulanan') {
            $start = Carbon::createFromDate($year, $month, 1, self::TZ)->startOfDay();
            $end   = $start->copy()->endOfMonth();
            $label = 'BULANAN ' . $start->locale('id')->translatedFormat('F Y');
        } else {
            // Semester / Tahunan: rentang dihitung oleh helper yang sama dengan rekap siswa.
            $semester = $request->query('period_semester') === 'genap' ? 'genap' : 'ganjil';
            $inputs = [
                'period_semester'      => $semester,
                'period_academic_year' => $request->query('period_academic_year'),
            ];
            $activeYear = AcademicYear::getActive();
            $range = MultiPeriodAttendanceExport::academicBounds($type, $inputs, $activeYear);
            $start = Carbon::parse($range['start'], self::TZ)->startOfDay();
            $end   = Carbon::parse($range['end'], self::TZ)->endOfDay();
            $ay    = AcademicYear::find($inputs['period_academic_year'] ?: 0) ?: $activeYear;
            $label = ($type === 'semester' ? 'SEMESTER ' . strtoupper($semester) : 'TAHUNAN') . ' ' . ($ay->name ?? '');
        }

        return compact('type', 'month', 'year', 'start', 'end', 'label', 'semester');
    }

    private function buildData(array $ctx): array
    {
        $teachers = Teacher::orderBy('name')->get();

        $attendances = TeacherAttendance::whereBetween('date', [$ctx['start']->toDateString(), $ctx['end']->toDateString()])
            ->get()
            ->groupBy('teacher_id')
            ->map(fn ($g) => $g->keyBy(fn ($a) => Carbon::parse($a->date)->toDateString()));

        $today = Carbon::now(self::TZ)->toDateString();
        $isMonthTable = in_array($ctx['type'], ['semester', 'tahunan'], true);

        $dateColumns = [];
        for ($c = $ctx['start']->copy(); $c->lte($ctx['end']); $c->addDay()) {
            $ds  = $c->toDateString();
            $hol = $c->isWeekend() || Holiday::isHoliday($ds);
            $dateColumns[] = [
                'date'       => $ds,
                'label'      => $ctx['type'] === 'bulanan' ? (string) $c->day : $c->copy()->locale('id')->translatedFormat('D, d/m'),
                'is_holiday' => $hol,
                'desc'       => $hol ? (Holiday::getHolidayDescription($ds) ?? ($c->isWeekend() ? 'Akhir Pekan' : 'Libur')) : null,
            ];
        }

        $isHoliday   = false;
        $holidayDesc = null;
        if ($ctx['type'] === 'harian') {
            $isHoliday   = $dateColumns[0]['is_holiday'];
            $holidayDesc = $dateColumns[0]['desc'];
        }

        $holidayMap = [];
        if ($ctx['type'] === 'bulanan') {
            foreach ($dateColumns as $col) {
                if ($col['is_holiday']) {
                    $holidayMap[(int) substr($col['date'], 8, 2)] = $col['desc'];
                }
            }
        }

        $months = $isMonthTable
            ? MultiPeriodAttendanceExport::monthColumnsBetween($ctx['start']->toDateString(), $ctx['end']->toDateString())
            : [];

        $effective = count(array_filter($dateColumns, fn ($c) => !$c['is_holiday']));
        $rows = [];

        foreach ($teachers as $t) {
            $byDate = $attendances->get($t->id, collect());
            $jk     = str_starts_with(strtolower((string) $t->gender), 'p') ? 'P' : 'L';

            if ($ctx['type'] === 'harian') {
                $col = $dateColumns[0];
                $a   = $byDate->get($col['date']);
                $code = $a ? $this->codeOf($a) : ($col['is_holiday'] ? 'L' : ($col['date'] < $today ? 'A' : '-'));

                $rows[] = [
                    'nip'       => $t->nip,
                    'name'      => $t->name,
                    'jk'        => $jk,
                    'check_in'  => $this->checkInText($a),
                    'status'    => $this->statusLabel($code),
                ];
                continue;
            }

            $days    = [];
            $sum     = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
            $monthly = [];
            $hasData = [];
            foreach ($months as $m) {
                $monthly[$m['key']] = 0;
                $hasData[$m['key']] = false;
            }

            foreach ($dateColumns as $col) {
                $a = $byDate->get($col['date']);
                if ($a) {
                    $code = $this->codeOf($a);
                } elseif ($col['is_holiday']) {
                    $code = 'L';
                } elseif ($col['date'] < $today) {
                    $code = 'A';
                } else {
                    $code = '-';
                }

                if (isset($sum[$code])) {
                    $sum[$code]++;
                }

                if ($isMonthTable) {
                    $mk = substr($col['date'], 0, 7);
                    if (isset($monthly[$mk])) {
                        if ($code !== '-' && $code !== 'L') {
                            $hasData[$mk] = true;
                        }
                        if ($code === 'H') {
                            $monthly[$mk]++;
                        }
                    }
                } else {
                    $days[$col['date']] = $code;
                }
            }

            $row = [
                'nip'        => $t->nip,
                'name'       => $t->name,
                'jk'         => $jk,
                'days'       => $days,
                'hadir'      => $sum['H'],
                'sakit'      => $sum['S'],
                'izin'       => $sum['I'],
                'alfa'       => $sum['A'],
                'percentage' => $effective > 0 ? (int) round(($sum['H'] / $effective) * 100) : 0,
            ];

            if ($isMonthTable) {
                $row['monthly'] = [];
                foreach ($months as $m) {
                    $row['monthly'][$m['key']] = $hasData[$m['key']] ? $monthly[$m['key']] : null;
                }
            }

            $rows[] = $row;
        }

        return [
            'dataRows'    => $rows,
            'dateColumns' => $dateColumns,
            'months'      => $months,
            'daysInMonth' => $ctx['type'] === 'bulanan' ? $ctx['start']->daysInMonth : count($dateColumns),
            'holidayMap'  => $holidayMap,
            'isHoliday'   => $isHoliday,
            'holidayDesc' => $holidayDesc,
        ];
    }

    /**
     * Jam masuk guru: pakai kolom check_in yang diisi scanner (jam scan sebenarnya).
     * created_at hanya cadangan untuk data lama yang check_in-nya kosong.
     */
    private function checkInText($a): string
    {
        if (!$a) {
            return '-';
        }

        if (!empty($a->check_in)) {
            return substr((string) $a->check_in, 0, 5);
        }

        return $a->created_at ? $a->created_at->timezone(self::TZ)->format('H:i') : '-';
    }

    private function codeOf($a): string
    {
        $s = strtolower((string) $a->status);

        if (str_contains($s, 'sakit')) {
            return 'S';
        }
        if (str_contains($s, 'izin')) {
            return 'I';
        }
        if (str_contains($s, 'alpa') || str_contains($s, 'alfa')) {
            return 'A';
        }
        if (str_contains($s, 'hadir')) {
            return 'H';
        }
        // Guru tidak mengenal status Terlambat: siswa/guru yang terlambat
        // tetap dihitung dan ditampilkan sebagai Hadir (H).
        if (str_contains($s, 'terlambat')) {
            return 'H';
        }

        return '-';
    }

    private function statusLabel(string $code): string
    {
        return match ($code) {
            'H' => 'Hadir',
            'S' => 'Sakit',
            'I' => 'Izin',
            'A' => 'Alfa',
            'L' => 'Libur',
            default => '-',
        };
    }
}
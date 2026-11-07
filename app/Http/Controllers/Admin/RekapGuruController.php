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
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

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
            $headings = ['NO', 'NIP', 'NAMA GURU', 'JK', 'JAM MASUK', 'KETERLAMBATAN', 'STATUS'];
            $out = [];
            foreach ($data['dataRows'] as $i => $r) {
                $out[] = [$i + 1, (string) $r['nip'], strtoupper($r['name']), $r['jk'], $r['check_in'], $r['late_text'], strtoupper($r['status'])];
            }
        } elseif (in_array($ctx['type'], ['semester', 'tahunan'], true)) {
            $headings = ['NO', 'NIP', 'NAMA GURU', 'JK'];
            foreach ($data['months'] as $m) {
                $headings[] = $m['label'] . ' ' . $m['year'];
            }
            array_push($headings, 'H', 'T', 'S', 'I', 'A', '%');
            $out = [];
            foreach ($data['dataRows'] as $i => $r) {
                $row = [$i + 1, (string) $r['nip'], strtoupper($r['name']), $r['jk']];
                foreach ($data['months'] as $m) {
                    $row[] = $r['monthly'][$m['key']] ?? '-';
                }
                array_push($row, $r['hadir'], $r['terlambat'], $r['sakit'], $r['izin'], $r['alfa'], $r['percentage'] . '%');
                $out[] = $row;
            }
        } else {
            $headings = ['NO', 'NIP', 'NAMA GURU', 'JK'];
            foreach ($data['dateColumns'] as $c) {
                $headings[] = $c['label'];
            }
            array_push($headings, 'H', 'T', 'S', 'I', 'A', '%');
            $out = [];
            foreach ($data['dataRows'] as $i => $r) {
                $row = [$i + 1, (string) $r['nip'], strtoupper($r['name']), $r['jk']];
                foreach ($data['dateColumns'] as $c) {
                    $row[] = $r['days'][$c['date']] ?? '-';
                }
                array_push($row, $r['hadir'], $r['terlambat'], $r['sakit'], $r['izin'], $r['alfa'], $r['percentage'] . '%');
                $out[] = $row;
            }
        }

        $export = new class($headings, $out) implements FromArray, WithHeadings, ShouldAutoSize {
            public function __construct(private array $headings, private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return $this->headings;
            }
        };

        return Excel::download($export, 'rekap-presensi-guru-' . $ctx['type'] . '-' . $ctx['start']->toDateString() . '.xlsx');
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
                'label'      => $ctx['type'] === 'bulanan' ? (string) $c->day : $c->copy()->locale('id')->translatedFormat('D d'),
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
                    'check_in'  => $a && $a->created_at ? $a->created_at->timezone(self::TZ)->format('H:i') : '-',
                    'late_text' => '-',
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
                        if ($code === 'H' || $code === 'T') {
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
                'terlambat'  => $sum['T'],
                'sakit'      => $sum['S'],
                'izin'       => $sum['I'],
                'alfa'       => $sum['A'],
                'percentage' => $effective > 0 ? (int) round((($sum['H'] + $sum['T']) / $effective) * 100) : 0,
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
        if (str_contains($s, 'terlambat') || str_contains($s, 'telat')) {
            return 'T';
        }
        if (str_contains($s, 'hadir')) {
            $r = strtolower((string) $a->time_remark);
            if ($r !== '' && !str_contains($r, 'tepat') && preg_match('/mnt|menit|jam/', $r)) {
                return 'T';
            }
            return 'H';
        }

        return '-';
    }

    private function statusLabel(string $code): string
    {
        return match ($code) {
            'H' => 'Hadir',
            'T' => 'Terlambat',
            'S' => 'Sakit',
            'I' => 'Izin',
            'A' => 'Alfa',
            'L' => 'Libur',
            default => '-',
        };
    }
}
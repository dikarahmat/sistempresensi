<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
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
        $ctx  = $this->context($request);
        $data = $this->buildData($ctx);

        return view('admin.attendances.rekap-guru', array_merge($data, [
            'type'      => $ctx['type'],
            'date'      => $ctx['start']->toDateString(),
            'startDate' => $ctx['start']->toDateString(),
            'endDate'   => $ctx['end']->toDateString(),
            'month'     => $ctx['month'],
            'year'      => $ctx['year'],
        ]));
    }

    public function exportPdf(Request $request)
    {
        $ctx  = $this->context($request);
        $data = $this->buildData($ctx);

        $pdf = Pdf::loadView('admin.attendances.pdf_rekap_guru', array_merge($data, [
            'type'        => $ctx['type'],
            'periodLabel' => $ctx['label'],
            'printedAt'   => Carbon::now(self::TZ)->locale('id')->translatedFormat('d F Y'),
        ]))->setPaper('a4', $ctx['type'] === 'harian' ? 'portrait' : 'landscape');

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
        $type = in_array($type, ['harian', 'mingguan', 'bulanan'], true) ? $type : 'harian';

        $month = max(1, min(12, (int) $request->query('month', $now->month)));
        $year  = (int) $request->query('year', $now->year);

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
        } else {
            $start = Carbon::createFromDate($year, $month, 1, self::TZ)->startOfDay();
            $end   = $start->copy()->endOfMonth();
            $label = 'BULANAN ' . $start->locale('id')->translatedFormat('F Y');
        }

        return compact('type', 'month', 'year', 'start', 'end', 'label');
    }

    private function buildData(array $ctx): array
    {
        $teachers = Teacher::orderBy('name')->get();

        $attendances = TeacherAttendance::whereBetween('date', [$ctx['start']->toDateString(), $ctx['end']->toDateString()])
            ->get()
            ->groupBy('teacher_id')
            ->map(fn ($g) => $g->keyBy(fn ($a) => Carbon::parse($a->date)->toDateString()));

        $today = Carbon::now(self::TZ)->toDateString();

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
                    'late_text' => $a ? ($code === 'H' ? 'Tepat Waktu' : ($code === 'T' ? ($a->time_remark ?: 'Terlambat') : '-')) : '-',
                    'status'    => $this->statusLabel($code),
                ];
                continue;
            }

            $days = [];
            $sum  = ['H' => 0, 'T' => 0, 'S' => 0, 'I' => 0, 'A' => 0];

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
                $days[$col['date']] = $code;
                if (isset($sum[$code])) {
                    $sum[$code]++;
                }
            }

            $rows[] = [
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
        }

        return [
            'dataRows'    => $rows,
            'dateColumns' => $dateColumns,
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
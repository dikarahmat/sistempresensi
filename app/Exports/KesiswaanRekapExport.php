<?php

namespace App\Exports;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KesiswaanRekapExport implements FromArray, ShouldAutoSize, WithStyles
{
    protected string $startDate;
    protected string $endDate;
    protected ?int $classId;
    protected ?string $search;
    protected int $headerRowIndex = 5;
    protected int $dataRowCount = 0;

    public function __construct(string $startDate, string $endDate, ?int $classId = null, ?string $search = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->classId = $classId;
        $this->search = $search;
    }

    public function array(): array
    {
        Carbon::setLocale('id');
        $schoolName = Setting::getSchoolName();
        $startFormatted = Carbon::parse($this->startDate)->translatedFormat('d F Y');
        $endFormatted = Carbon::parse($this->endDate)->translatedFormat('d F Y');

        $selectedClass = $this->classId ? SchoolClass::with('teacher')->find($this->classId) : null;
        $className = $selectedClass ? $selectedClass->name : 'Semua Kelas';

        $output = [];

        // Judul Laporan & Kop
        $output[] = ["LAPORAN REKAPITULASI PRESENSI GLOBAL - " . strtoupper($schoolName)];
        $output[] = ["BAGIAN KESISWAAN & KEMAHASISWAAN"];
        $output[] = ["Periode: {$startFormatted} s/d {$endFormatted} | Kelas: {$className}"];
        $output[] = []; // Baris kosong

        // Header Kolom Tabel
        $headers = [
            'No',
            'NIS',
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Wali Kelas',
            'JK',
            'Hadir (H)',
            'Terlambat (T)',
            'Sakit (S)',
            'Izin (I)',
            'Alfa (A)',
            'Persentase (%)',
        ];
        $output[] = $headers;

        // Query Siswa
        $query = Student::where('status', 'Aktif')->with(['schoolClass.teacher']);
        if ($this->classId) {
            $query->where('school_class_id', $this->classId);
        }
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('nis', 'like', "%{$this->search}%")
                  ->orWhere('nisn', 'like', "%{$this->search}%");
            });
        }
        $students = $query->orderBy('school_class_id')->orderBy('name')->get();

        // Hitung Hari Efektif dalam rentang tanggal
        $period = CarbonPeriod::create($this->startDate, $this->endDate);
        $effectiveDaysCount = 0;
        foreach ($period as $date) {
            $dStr = $date->toDateString();
            if (!$date->isSunday() && !Holiday::isHoliday($dStr)) {
                $effectiveDaysCount++;
            }
        }
        if ($effectiveDaysCount === 0) {
            $effectiveDaysCount = 1;
        }

        // Ambil data presensi
        $attendances = Attendance::whereBetween('date', [$this->startDate, $this->endDate])
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');

        $no = 1;
        $totalH = 0;
        $totalT = 0;
        $totalS = 0;
        $totalI = 0;
        $totalA = 0;

        foreach ($students as $student) {
            $stAtts = $attendances->get($student->id, collect());

            $h = $stAtts->where('status', 'Hadir')->count();
            $t = $stAtts->where('status', 'Hadir')->where('time_remark', 'Terlambat')->count();
            $s = $stAtts->where('status', 'Sakit')->count();
            $i = $stAtts->where('status', 'Izin')->count();
            $a = $stAtts->where('status', 'Alfa')->count();

            $totalH += $h;
            $totalT += $t;
            $totalS += $s;
            $totalI += $i;
            $totalA += $a;

            $pct = round(($h / $effectiveDaysCount) * 100);
            if ($pct > 100) $pct = 100;

            $output[] = [
                $no++,
                $student->nis,
                $student->nisn ?? '-',
                $student->name,
                $student->schoolClass?->name ?? '-',
                $student->schoolClass?->teacher?->name ?? '-',
                $student->gender === 'Laki-laki' ? 'L' : 'P',
                $h,
                $t,
                $s,
                $i,
                $a,
                $pct . '%',
            ];
        }

        $this->dataRowCount = count($students);

        // Baris Total Akumulasi
        $avgPct = $this->dataRowCount > 0 ? round(($totalH / ($this->dataRowCount * $effectiveDaysCount)) * 100) : 0;
        if ($avgPct > 100) $avgPct = 100;

        $output[] = [
            'TOTAL / RATA-RATA',
            '',
            '',
            '',
            '',
            '',
            '',
            $totalH,
            $totalT,
            $totalS,
            $totalI,
            $totalA,
            $avgPct . '%',
        ];

        return $output;
    }

    public function styles(Worksheet $sheet): array
    {
        $headerRow = $this->headerRowIndex;
        $lastRow = $headerRow + $this->dataRowCount + 1; // +1 untuk baris summary

        // Merge Header Judul
        $sheet->mergeCells("A1:M1");
        $sheet->mergeCells("A2:M2");
        $sheet->mergeCells("A3:M3");

        // Merge Total Label di baris terakhir
        $sheet->mergeCells("A{$lastRow}:G{$lastRow}");

        return [
            // Baris Judul
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1D4ED8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],

            // Baris Header Tabel
            $headerRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6'], // Primary Blue #3b82f6
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '93C5FD'],
                    ],
                ],
            ],

            // Baris Total / Summary
            $lastRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ],

            // Garis pembatas isi tabel
            "A{$headerRow}:M{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}

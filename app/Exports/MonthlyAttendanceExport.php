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
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyAttendanceExport implements FromArray, ShouldAutoSize, WithStyles
{
    protected int $month;
    protected int $year;
    protected ?int $classId;
    protected int $daysInMonth;
    protected array $rows = [];
    protected array $holidayDays = [];
    protected int $dataStartRow = 5;
    protected int $dataEndRow = 5;

    public function __construct(int $month, int $year, ?int $classId = null)
    {
        $this->month = $month;
        $this->year = $year;
        $this->classId = $classId;
        $this->daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
    }

    public function array(): array
    {
        Carbon::setLocale('id');
        $schoolName = Setting::getSchoolName();
        $dateObj = Carbon::createFromDate($this->year, $this->month, 1);
        $monthName = $dateObj->translatedFormat('F');
        $activeYear = AcademicYear::getActive();

        $selectedClass = $this->classId ? SchoolClass::with('teacher')->find($this->classId) : null;
        $className = $selectedClass ? $selectedClass->name : 'Semua Kelas';

        // Cari hari libur di bulan ini
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $curDate = Carbon::createFromDate($this->year, $this->month, $d)->toDateString();
            $isSunday = Carbon::createFromDate($this->year, $this->month, $d)->isSunday();
            if ($isSunday || Holiday::isHoliday($curDate)) {
                $this->holidayDays[] = $d;
            }
        }

        // Header Dokumen
        $output = [];
        $output[] = ["LAPORAN REKAPITULASI PRESENSI SISWA - {$schoolName}"];
        $output[] = ["Bulan: {$monthName} {$this->year} | Kelas: {$className} | Tahun Ajaran: " . ($activeYear ? $activeYear->name : '-')];
        $output[] = []; // Baris kosong

        // Header Kolom Tabel
        $headerRow = ['No', 'NIS', 'Nama Siswa', 'JK'];
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $headerRow[] = (string) $d;
        }
        $headerRow[] = 'H';
        $headerRow[] = 'T';
        $headerRow[] = 'S';
        $headerRow[] = 'I';
        $headerRow[] = 'A';
        $headerRow[] = '% Hadir';
        $output[] = $headerRow;

        // Query Siswa
        $query = Student::where('status', 'Aktif')->with('schoolClass');
        if ($this->classId) {
            $query->where('school_class_id', $this->classId);
        }
        $students = $query->orderBy('name')->get();

        // Query Absensi Bulan Ini
        $startDate = Carbon::createFromDate($this->year, $this->month, 1)->toDateString();
        $endDate = Carbon::createFromDate($this->year, $this->month, $this->daysInMonth)->toDateString();

        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');

        $now = Carbon::now('Asia/Jakarta');
        $currentDateString = $now->toDateString();

        $rowIndex = 1;
        foreach ($students as $student) {
            $studentAtts = $attendances->get($student->id, collect())->keyBy(function ($item) {
                return Carbon::parse($item->date)->day;
            });

            $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;

            $row = [
                $rowIndex++,
                $student->nis,
                $student->name,
                $student->gender == 'Perempuan' ? 'P' : 'L',
            ];

            for ($d = 1; $d <= $this->daysInMonth; $d++) {
                $cDate = Carbon::createFromDate($this->year, $this->month, $d)->toDateString();
                $isSunday = Carbon::createFromDate($this->year, $this->month, $d)->isSunday();
                $isHol = Holiday::isHoliday($cDate);

                if ($isSunday || $isHol) {
                    $row[] = 'L'; // Libur
                } else {
                    $att = $studentAtts->get($d);
                    if ($att) {
                        if ($att->status == 'Hadir') {
                            if ($att->time_remark == 'Terlambat') {
                                $row[] = 'T';
                                $t++;
                                $h++; // Terlambat tetap dihitung hadir
                            } else {
                                $row[] = 'H';
                                $h++;
                            }
                        } elseif ($att->status == 'Sakit') {
                            $row[] = 'S';
                            $s++;
                        } elseif ($att->status == 'Izin') {
                            $row[] = 'I';
                            $i++;
                        } else {
                            $row[] = 'A';
                            $a++;
                        }
                    } else {
                        // Jika hari kerja sudah lewat / hari ini dan tidak ada absen
                        if ($cDate <= $currentDateString) {
                            $row[] = 'A';
                            $a++;
                        } else {
                            $row[] = '-';
                        }
                    }
                }
            }

            $effectiveDays = ($this->daysInMonth - count($this->holidayDays));
            $pct = $effectiveDays > 0 ? round(($h / $effectiveDays) * 100) : 0;

            $row[] = $h;
            $row[] = $t;
            $row[] = $s;
            $row[] = $i;
            $row[] = $a;
            $row[] = $pct . '%';

            $output[] = $row;
        }

        $this->dataEndRow = count($output);

        // Keterangan & Tanda Tangan
        $output[] = [];
        $output[] = ['Keterangan: H = Hadir, T = Terlambat, S = Sakit, I = Izin, A = Alpha, L = Libur'];
        $output[] = [];
        $output[] = ['', '', '', '', 'Mengetahui,', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 'Wali Kelas ' . $className];
        $output[] = ['', '', '', '', 'Kepala Sekolah', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ($selectedClass?->teacher?->name ?? '...........................')];
        $output[] = [];
        $output[] = [];
        $output[] = ['', '', '', '', '( ...................................... )', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '( ' . ($selectedClass?->teacher?->name ?? '...........................') . ' )'];

        $this->rows = $output;
        return $output;
    }

    public function styles(Worksheet $sheet)
    {
        // Judul
        $sheet->mergeCells('A1:AJ1');
        $sheet->mergeCells('A2:AJ2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));

        // Baris Header Kolom (Baris 4)
        $totalCols = 4 + $this->daysInMonth + 6;
        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

        $sheet->getStyle("A4:{$highestColumn}4")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A8A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Border & Alignment data siswa
        if ($this->dataEndRow >= 5) {
            $sheet->getStyle("A4:{$highestColumn}{$this->dataEndRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFCBD5E1'],
                    ],
                ],
            ]);

            // Tengahkan kolom nomor, NIS, JK, tanggal 1-31, dan summary
            $sheet->getStyle("A5:A{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B5:B{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D5:{$highestColumn}{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}

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

class MultiPeriodAttendanceExport implements FromArray, ShouldAutoSize, WithStyles
{
    protected string $type;
    protected array $params;
    protected int $dataStartRow = 5;
    protected int $dataEndRow = 5;
    protected int $columnCount = 8;

    public function __construct(string $type, array $params = [])
    {
        $this->type = in_array($type, ['harian', 'mingguan', 'bulanan']) ? $type : 'bulanan';
        $this->params = $params;
    }

    public function array(): array
    {
        Carbon::setLocale('id');
        $schoolName = Setting::getSchoolName();
        $activeYear = AcademicYear::getActive();
        $classId = $this->params['class_id'] ?? null;
        $selectedClass = $classId ? SchoolClass::with('teacher')->find($classId) : null;
        $className = $selectedClass ? $selectedClass->name : 'Semua Kelas';

        $output = [];

        if ($this->type === 'harian') {
            $date = $this->params['date'] ?? Carbon::now('Asia/Jakarta')->toDateString();
            $dateFormatted = Carbon::parse($date)->translatedFormat('l, d F Y');

            $output[] = ["LAPORAN REKAPITULASI PRESENSI HARIAN - {$schoolName}"];
            $output[] = ["Tanggal: {$dateFormatted} | Kelas: {$className} | Tahun Ajaran: " . ($activeYear ? $activeYear->name : '-')];
            $output[] = [];

            $headerRow = ['No', 'NIS', 'Nama Siswa', 'Kelas', 'JK', 'Jam Masuk', 'Keterlambatan', 'Status', 'Catatan'];
            $output[] = $headerRow;
            $this->columnCount = count($headerRow);

            $query = Student::where('status', 'Aktif')->with('schoolClass');
            if ($classId) {
                $query->where('school_class_id', $classId);
            }
            $students = $query->orderBy('name')->get();

            $attendances = Attendance::where('date', $date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');

            $lateLimitTime = Setting::getLateLimitTime();
            $isSunday = Carbon::parse($date)->isSunday();
            $isHoliday = Holiday::isHoliday($date) || $isSunday;

            $now = Carbon::now('Asia/Jakarta');
            $rowNum = 1;

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
                            $diff = Carbon::parse($att->check_in)->diffInMinutes(Carbon::parse($lateLimitTime), false);
                            $lateMinutes = $diff < 0 ? abs($diff) : 0;
                            $lateText = "Terlambat (+{$lateMinutes}m)";
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

                $output[] = [
                    $rowNum++,
                    $student->nis,
                    $student->name,
                    $student->schoolClass ? $student->schoolClass->name : '-',
                    $student->gender === 'Laki-laki' ? 'L' : 'P',
                    $checkIn,
                    $lateText,
                    $status,
                    $notes,
                ];
            }

            $this->dataEndRow = count($output);
        } elseif ($this->type === 'mingguan') {
            $startDate = $this->params['start_date'] ?? Carbon::now('Asia/Jakarta')->startOfWeek()->toDateString();
            $endDate = $this->params['end_date'] ?? Carbon::now('Asia/Jakarta')->startOfWeek()->addDays(4)->toDateString();

            $startObj = Carbon::parse($startDate);
            $endObj = Carbon::parse($endDate);
            $periodStr = $startObj->translatedFormat('d F Y') . ' s/d ' . $endObj->translatedFormat('d F Y');

            $output[] = ["LAPORAN REKAPITULASI PRESENSI MINGGUAN - {$schoolName}"];
            $output[] = ["Periode: {$periodStr} | Kelas: {$className} | Tahun Ajaran: " . ($activeYear ? $activeYear->name : '-')];
            $output[] = [];

            // Buat daftar tanggal kerja dalam minggu
            $dates = [];
            $cur = $startObj->copy();
            while ($cur->lte($endObj)) {
                $dates[] = $cur->copy();
                $cur->addDay();
            }

            $headerRow = ['No', 'NIS', 'Nama Siswa', 'Kelas', 'JK'];
            foreach ($dates as $d) {
                $headerRow[] = $d->translatedFormat('D, d/m');
            }
            $headerRow[] = 'H';
            $headerRow[] = 'T';
            $headerRow[] = 'S';
            $headerRow[] = 'I';
            $headerRow[] = 'A';
            $headerRow[] = '% Hadir';
            $output[] = $headerRow;
            $this->columnCount = count($headerRow);

            $query = Student::where('status', 'Aktif')->with('schoolClass');
            if ($classId) {
                $query->where('school_class_id', $classId);
            }
            $students = $query->orderBy('name')->get();

            $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->groupBy('student_id');

            $rowNum = 1;
            foreach ($students as $student) {
                $stAtts = $attendances->get($student->id, collect())->keyBy('date');
                $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;

                $row = [
                    $rowNum++,
                    $student->nis,
                    $student->name,
                    $student->schoolClass ? $student->schoolClass->name : '-',
                    $student->gender === 'Laki-laki' ? 'L' : 'P',
                ];

                foreach ($dates as $d) {
                    $dStr = $d->toDateString();
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
                        if ($d->isSunday() || Holiday::isHoliday($dStr)) {
                            $code = 'L';
                        } elseif ($dStr <= Carbon::now('Asia/Jakarta')->toDateString()) {
                            $code = 'A';
                            $a++;
                        }
                    }

                    $row[] = $code;
                }

                $totalDays = count($dates);
                $pct = $totalDays > 0 ? round(($h / $totalDays) * 100) : 0;

                $row[] = $h;
                $row[] = $t;
                $row[] = $s;
                $row[] = $i;
                $row[] = $a;
                $row[] = $pct . '%';

                $output[] = $row;
            }

            $this->dataEndRow = count($output);
        } else {
            // BULANAN
            $now = Carbon::now('Asia/Jakarta');
            $month = (int) ($this->params['month'] ?? $now->month);
            $year = (int) ($this->params['year'] ?? $now->year);
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F');

            $output[] = ["LAPORAN REKAPITULASI PRESENSI BULANAN - {$schoolName}"];
            $output[] = ["Bulan: {$monthName} {$year} | Kelas: {$className} | Tahun Ajaran: " . ($activeYear ? $activeYear->name : '-')];
            $output[] = [];

            $headerRow = ['No', 'NIS', 'Nama Siswa', 'Kelas', 'JK'];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $headerRow[] = (string) $d;
            }
            $headerRow[] = 'H';
            $headerRow[] = 'T';
            $headerRow[] = 'S';
            $headerRow[] = 'I';
            $headerRow[] = 'A';
            $headerRow[] = '% Hadir';
            $output[] = $headerRow;
            $this->columnCount = count($headerRow);

            $query = Student::where('status', 'Aktif')->with('schoolClass');
            if ($classId) {
                $query->where('school_class_id', $classId);
            }
            $students = $query->orderBy('name')->get();

            $startDate = Carbon::createFromDate($year, $month, 1)->toDateString();
            $endDate = Carbon::createFromDate($year, $month, $daysInMonth)->toDateString();

            $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->groupBy('student_id');

            $curDateStr = $now->toDateString();
            $rowNum = 1;

            foreach ($students as $student) {
                $stAtts = $attendances->get($student->id, collect())->keyBy(function ($item) {
                    return Carbon::parse($item->date)->day;
                });

                $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;

                $row = [
                    $rowNum++,
                    $student->nis,
                    $student->name,
                    $student->schoolClass ? $student->schoolClass->name : '-',
                    $student->gender === 'Laki-laki' ? 'L' : 'P',
                ];

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $dDate = Carbon::createFromDate($year, $month, $d)->toDateString();
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
                        $isSun = Carbon::createFromDate($year, $month, $d)->isSunday();
                        $isHol = Holiday::isHoliday($dDate);
                        if ($isSun || $isHol) {
                            $code = 'L';
                        } elseif ($dDate <= $curDateStr) {
                            $code = 'A';
                            $a++;
                        }
                    }

                    $row[] = $code;
                }

                $pct = $daysInMonth > 0 ? round(($h / $daysInMonth) * 100) : 0;
                $row[] = $h;
                $row[] = $t;
                $row[] = $s;
                $row[] = $i;
                $row[] = $a;
                $row[] = $pct . '%';

                $output[] = $row;
            }

            $this->dataEndRow = count($output);
        }

        return $output;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($this->columnCount);

        // Header Title
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1D4ED8'));
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));

        // Table Header
        $tableHeaderRange = "A4:{$lastCol}4";
        $sheet->getStyle($tableHeaderRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($tableHeaderRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('2563EB');
        $sheet->getStyle($tableHeaderRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Data Rows Borders
        if ($this->dataEndRow >= 5) {
            $dataRange = "A4:{$lastCol}{$this->dataEndRow}";
            $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('CBD5E1');
            $sheet->getStyle("A5:A{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B5:B{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D5:E{$this->dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
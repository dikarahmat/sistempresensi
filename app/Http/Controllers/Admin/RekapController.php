<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Exports\MultiPeriodAttendanceExport;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class RekapController extends Controller
{
    protected function getRecapData(string $type, ?int $classId, array $inputs, bool $paginateStudents = false): array
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $type = in_array($type, ['harian', 'mingguan', 'bulanan', 'semester', 'tahunan']) ? $type : 'bulanan';
        $activeYear = AcademicYear::getActive();

        $query = Student::with('schoolClass');
        if ($classId) {
            $query->where('school_class_id', $classId);
        }
        $studentPaginator = $paginateStudents
            ? $query->orderBy('name', 'asc')->paginate(100)->withQueryString()
            : null;
        $students = $studentPaginator
            ? $studentPaginator->getCollection()
            : $query->orderBy('name', 'asc')->get();

        $totalHadir = 0;
        $totalTerlambat = 0;
        $totalSakit = 0;
        $totalIzin = 0;
        $totalAlfa = 0;
        $dataRows = [];

        $lateLimitTime = Setting::getLateLimitTime();

        $dateRange = $this->resolveDateRange($type, $inputs, $now, $activeYear);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        $periodLabel = $dateRange['label'];

        if ($type === 'harian') {
            $date = $inputs['date'] ?? $now->toDateString();
            $dateObj = Carbon::parse($date);
            $isWeekend = $dateObj->isWeekend();
            $isHoliday = Holiday::isHoliday($date) || $isWeekend;
            $holidayDesc = Holiday::getHolidayDescription($date) ?? ($isWeekend ? 'Akhir Pekan' : null);

            $attendances = Attendance::where('date', $date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');

            foreach ($students as $student) {
                $att = $attendances->get($student->id);
                $row = Attendance::dataRowForRekap($att, $lateLimitTime);

                if ($att) {
                    $status = $row['status'];
                    if ($status === Attendance::STATUS_TERLAMBAT) {
                        $totalTerlambat++;
                        $totalHadir++;
                    } elseif ($status === Attendance::STATUS_HADIR) {
                        $totalHadir++;
                    } elseif ($status === Attendance::STATUS_SAKIT) {
                        $totalSakit++;
                    } elseif ($status === Attendance::STATUS_IZIN) {
                        $totalIzin++;
                    } elseif ($status === Attendance::STATUS_ALFA) {
                        $totalAlfa++;
                    }
                } else {
                    $status = Attendance::statusOf(null, $date, $isHoliday);
                }

                // Jam pulang: sumber yang sama dengan ekspor Excel (makeDailyRow).
                $dailyRow = Attendance::makeDailyRow($att, $date, $lateLimitTime, $status, $student->school_class_id);

                $dataRows[] = [
                    'student' => $student,
                    'check_in' => $row['check_in'],
                    'check_out' => $dailyRow['check_out'] ?? $row['check_out'] ?? '-',
                    'late_text' => $row['late_text'],
                    'late_minutes' => $row['late_minutes'],
                    'status' => $status,
                    'notes' => $row['notes'],
                    'proof_document' => $row['proof_document'],
                ];
            }

            return compact(
                'type', 'date', 'dateObj', 'isHoliday', 'holidayDesc',
                'students', 'dataRows', 'totalHadir', 'totalTerlambat',
                'totalSakit', 'totalIzin', 'totalAlfa', 'studentPaginator'
            );
        }

        $dateColumns = [];
        $cur = Carbon::parse($startDate)->copy();
        $endObj = Carbon::parse($endDate);
        while ($cur->lte($endObj)) {
            $curDateStr = $cur->toDateString();
            $isWeekend = $cur->isWeekend();
            $isHol = Holiday::isHoliday($curDateStr);
            $dateColumns[] = [
                'date' => $curDateStr,
                'carbon' => $cur->copy(),
                'label' => $cur->translatedFormat('D, d/m'),
                'is_holiday' => $isWeekend || $isHol,
            ];
            $cur->addDay();
        }

        $totalEffective = count(array_filter($dateColumns, fn($c) => !$c['is_holiday']));
        $isMonthTable = in_array($type, ['semester', 'tahunan'], true);

        if ($isMonthTable) {
            $monthlyCounts = MultiPeriodAttendanceExport::monthlyStatusCounts(
                $startDate,
                $endDate,
                $students->pluck('id')
            );
            $months = MultiPeriodAttendanceExport::monthColumnsBetween($startDate, $endDate);

            foreach ($students as $student) {
                $perMonth = $monthlyCounts[$student->id] ?? [];
                $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;
                $monthly = [];

                foreach ($months as $monthInfo) {
                    $data = $perMonth[$monthInfo['key']] ?? null;
                    if ($data === null) {
                        $monthly[$monthInfo['key']] = null;
                        continue;
                    }

                    $h += $data['hadir'] + $data['terlambat'];
                    $t += $data['terlambat'];
                    $s += $data['sakit'];
                    $i += $data['izin'];
                    $a += $data['alfa'];
                    $monthly[$monthInfo['key']] = $data['hadir'] + $data['terlambat'];
                }

                $percentage = Attendance::attendancePercentage((int) $h, (int) $totalEffective);

                $totalHadir += $h;
                $totalTerlambat += $t;
                $totalSakit += $s;
                $totalIzin += $i;
                $totalAlfa += $a;

                $dataRows[] = [
                    'student' => $student,
                    'days' => [],
                    'monthly' => $monthly,
                    'hadir' => $h,
                    'terlambat' => $t,
                    'sakit' => $s,
                    'izin' => $i,
                    'alfa' => $a,
                    'percentage' => $percentage,
                ];
            }

            $monthExtra = [
                'type' => $type,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'periodLabel' => $periodLabel,
                'dateColumns' => $dateColumns,
                'students' => $students,
                'dataRows' => $dataRows,
                'totalHadir' => $totalHadir,
                'totalTerlambat' => $totalTerlambat,
                'totalSakit' => $totalSakit,
                'totalIzin' => $totalIzin,
                'totalAlfa' => $totalAlfa,
                'studentPaginator' => $studentPaginator,
                'months' => $months,
                'period_academic_year' => $inputs['period_academic_year'] ?? ($activeYear?->id ?? null),
            ];
            if ($type === 'semester') {
                $monthExtra['period_semester'] = $inputs['period_semester'] ?? 'ganjil';
            }

            return $monthExtra;
        }

        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');

        foreach ($students as $student) {
            $stAtts = $attendances->get($student->id, collect())->keyBy(fn($a) => $a->date->toDateString());
            $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;
            $days = [];

            foreach ($dateColumns as $col) {
                $dStr = $col['date'];
                $att = $stAtts->get($dStr);
                $code = '-';

                if ($att) {
                    $status = $att->effective_status;
                    if ($status === 'Hadir') {
                        $code = 'H';
                        $h++;
                    } elseif ($status === 'Terlambat') {
                        $code = 'T';
                        $t++;
                        $h++;
                    } elseif ($status === 'Sakit') {
                        $code = 'S';
                        $s++;
                    } elseif ($status === 'Izin') {
                        $code = 'I';
                        $i++;
                    } elseif ($status === 'Alfa') {
                        $code = 'A';
                        $a++;
                    }
                } else {
                    if ($col['is_holiday']) {
                        $code = 'L';
                    } else {
                        $code = '-';
                    }
                }

                $days[$dStr] = $code;
            }

            $totalEffective = count(array_filter($dateColumns, fn($c) => !$c['is_holiday']));
            $percentage = Attendance::attendancePercentage((int) $h, (int) $totalEffective);

            $totalHadir += $h;
            $totalTerlambat += $t;
            $totalSakit += $s;
            $totalIzin += $i;
            $totalAlfa += $a;

            $dataRows[] = [
                'student' => $student,
                'days' => $days,
                'hadir' => $h,
                'terlambat' => $t,
                'sakit' => $s,
                'izin' => $i,
                'alfa' => $a,
                'percentage' => $percentage,
            ];
        }

        $extra = [
            'type' => $type,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'periodLabel' => $periodLabel,
            'dateColumns' => $dateColumns,
            'students' => $students,
            'dataRows' => $dataRows,
            'totalHadir' => $totalHadir,
            'totalTerlambat' => $totalTerlambat,
            'totalSakit' => $totalSakit,
            'totalIzin' => $totalIzin,
            'totalAlfa' => $totalAlfa,
            'studentPaginator' => $studentPaginator,
        ];

        if ($type === 'bulanan') {
            $month = (int) ($inputs['month'] ?? $now->month);
            $year = (int) ($inputs['year'] ?? $now->year);
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $holidayMap = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $cDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $isWeekend = Carbon::createFromDate($year, $month, $d)->isWeekend();
                $isHol = Holiday::isHoliday($cDate);
                if ($isWeekend || $isHol) {
                    $holidayMap[$d] = Holiday::getHolidayDescription($cDate) ?? 'Akhir Pekan';
                }
            }
            $extra['month'] = $month;
            $extra['year'] = $year;
            $extra['daysInMonth'] = $daysInMonth;
            $extra['holidayMap'] = $holidayMap;
        }

        return $extra;
    }

    protected function resolveDateRange(string $type, array $inputs, Carbon $now, ?AcademicYear $activeYear): array
    {
        switch ($type) {
            case 'harian':
                $date = $inputs['date'] ?? $now->toDateString();
                $date = Carbon::parse($date)->toDateString();
                return [
                    'start' => $date,
                    'end' => $date,
                    'label' => Carbon::parse($date)->translatedFormat('l, d F Y'),
                ];

            case 'mingguan':
                if (!empty($inputs['start_date']) || !empty($inputs['end_date'])) {
                    $startDate = Carbon::parse($inputs['start_date'] ?? $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString())->toDateString();
                    $endDate = Carbon::parse($inputs['end_date'] ?? $now->copy()->startOfWeek(Carbon::MONDAY)->addDays(6)->toDateString())->toDateString();
                } else {
                    $anchor = Carbon::parse($inputs['date'] ?? $now->toDateString());
                    $startDate = $anchor->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
                    $endDate = $anchor->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();
                }
                if ($endDate < $startDate) {
                    [$startDate, $endDate] = [$endDate, $startDate];
                }
                return [
                    'start' => $startDate,
                    'end' => $endDate,
                    'label' => Carbon::parse($startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($endDate)->translatedFormat('d M Y'),
                ];

            case 'bulanan':
                $month = (int) ($inputs['month'] ?? $now->month);
                $year = (int) ($inputs['year'] ?? $now->year);
                if ($month < 1 || $month > 12) {
                    $month = (int) $now->month;
                }
                $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
                $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
                return [
                    'start' => $startDate,
                    'end' => $endDate,
                    'label' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
                ];

            case 'semester':
                $semester = ($inputs['period_semester'] ?? 'ganjil') === 'genap' ? 'genap' : 'ganjil';
                $ay = AcademicYear::find($inputs['period_academic_year'] ?? ($activeYear?->id ?? 0)) ?: $activeYear;
                $range = MultiPeriodAttendanceExport::academicBounds('semester', $inputs, $activeYear);
                $startDate = $range['start'];
                $endDate = $range['end'];

                return [
                    'start' => $startDate,
                    'end' => $endDate,
                    'label' => 'Semester ' . ucfirst($semester) . ' ' . ($ay->name ?? Carbon::parse($startDate)->format('Y')),
                ];

            case 'tahunan':
                $ay = AcademicYear::find($inputs['period_academic_year'] ?? ($activeYear?->id ?? 0)) ?: $activeYear;
                $range = MultiPeriodAttendanceExport::academicBounds('tahunan', $inputs, $activeYear);
                $startDate = $range['start'];
                $endDate = $range['end'];

                return [
                    'start' => $startDate,
                    'end' => $endDate,
                    'label' => 'Tahun Ajaran ' . ($ay->name ?? Carbon::parse($startDate)->format('Y')),
                ];

            default:
                return [
                    'start' => $now->startOfMonth()->toDateString(),
                    'end' => $now->endOfMonth()->toDateString(),
                    'label' => $now->translatedFormat('F Y'),
                ];
        }
    }

    protected function studentProfileFileName(array $studentsData, string $periodType, ?string $studentScope, string $extension): string
    {
        $first = $studentsData[0]['student'] ?? null;

        if ($studentScope === 'class' && $first?->schoolClass?->name) {
            $scope = Str::slug($first->schoolClass->name);
        } elseif ($studentScope === 'single' && $first) {
            $scope = Str::slug($first->nisn ?: $first->name);
        } else {
            $scope = Str::slug(Setting::getSchoolName() ?: 'sekolah');
        }

        return "Profil_Presensi_{$periodType}_{$scope}.{$extension}";
    }

    protected function paginateRecapRows(array $recap): array
    {
        $paginator = $recap['studentPaginator'] ?? null;
        if ($paginator instanceof LengthAwarePaginator) {
            $paginator->setCollection(collect($recap['dataRows'] ?? [])->values());
            $recap['dataRows'] = $paginator;
        }

        unset($recap['studentPaginator']);

        return $recap;
    }

    public function index(Request $request): View
    {
        $type = $request->input('type', 'harian');
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $classes = SchoolClass::orderBy('name')->get();
        $activeYear = AcademicYear::getActive();

        $recap = $this->getRecapData($type, $classId, $request->all(), true);
        $recap = $this->paginateRecapRows($recap);

        return view('admin.attendances.rekap', array_merge($recap, [
            'type' => $type,
            'classId' => $classId,
            'classes' => $classes,
            'activeYear' => $activeYear,
            'popupDate' => $request->input('date') ?: Carbon::now('Asia/Jakarta')->toDateString(),
            'popupMonth' => (int) ($request->input('month') ?: Carbon::now('Asia/Jakarta')->month),
            'popupYear' => (int) ($request->input('year') ?: Carbon::now('Asia/Jakarta')->year),
            'academicYears' => AcademicYear::orderBy('start_date', 'desc')->get(),
            'siswaPerKelas' => Student::where('status', 'Aktif')
                ->selectRaw('school_class_id, COUNT(*) as jml')
                ->groupBy('school_class_id')
                ->pluck('jml', 'school_class_id')
                ->all(),
            'siswaTotal' => Student::where('status', 'Aktif')->count(),
            'kelasCetak' => $classes->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'jml' => (int) (Student::where('status', 'Aktif')->where('school_class_id', $c->id)->count()),
            ])->values(),
        ]));
    }

    public function exportExcel(Request $request)
    {
        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '180');

            $type = $request->input('type', 'bulanan');
            $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
            $selectedClass = $classId ? SchoolClass::find($classId) : null;
            $className = $selectedClass ? Str::slug($selectedClass->name) : 'semua_kelas';

            $fileName = "Rekap_Presensi_{$type}_{$className}_" . date('Ymd_His') . ".xlsx";

            return \App\Services\DownloadCacheService::downloadRekapFile('xlsx', $type, $classId, $request->all(), function() use ($type, $classId, $request, $fileName) {
                return Excel::download(new MultiPeriodAttendanceExport($type, array_merge($request->all(), ['class_id' => $classId])), $fileName);
            }, $fileName);
        } catch (\Throwable $e) {
            Log::error('Gagal export Excel: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', __('Gagal membuat file export. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '180');

            Carbon::setLocale('id');

            $type = $request->input('type', 'bulanan');
            $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
            $selectedClass = $classId ? SchoolClass::with('teacher')->find($classId) : null;
            $className = $selectedClass ? $selectedClass->name : 'Semua Kelas';

            $fileName = "Rekap_Presensi_{$type}_" . Str::slug($className) . ".pdf";

            return \App\Services\DownloadCacheService::downloadRekapFile('pdf', $type, $classId, $request->all(), function() use ($type, $classId, $request, $className) {
                $schoolName = Setting::getSchoolName();
                $schoolAddress = Setting::getSchoolAddress();
                $activeYear = AcademicYear::getActive();

                $recap = $this->getRecapData($type, $classId, $request->all());

                $sections = MultiPeriodAttendanceExport::buildSections(
                    $recap['dataRows'],
                    $type,
                    $request->all(),
                    $classId
                );

                $logoBase64 = $this->getLogoBase64();

                $pdf = Pdf::loadView('admin.attendances.pdf_multi_rekap', array_merge($recap, [
                    'title' => "Laporan Presensi {$type} - {$className}",
                    'schoolName' => $schoolName,
                    'schoolAddress' => $schoolAddress,
                    'logoBase64' => $logoBase64,
                    'activeYear' => $activeYear,
                    'headmasterName' => Setting::getHeadmasterName(),
                    'headmasterNip' => Setting::getHeadmasterNip(),
                    'sections' => $sections,
                ]))->setPaper('a4', 'landscape');

                return $pdf->output();
            }, $fileName);
        } catch (\Throwable $e) {
            Log::error('Gagal export PDF: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', __('Gagal membuat file export. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function studentSearch(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = trim($request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $students = Student::with('schoolClass')
            ->where('status', 'Aktif')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('nisn', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'nisn', 'school_class_id'])
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'nisn' => $s->nisn,
                    'class_name' => $s->schoolClass?->name ?? '-',
                ];
            });

        return response()->json($students);
    }

    public function printRekap(Request $request)
    {
        try {
            $validated = $request->validate([
                'format' => 'required|in:pdf,excel',
                'type' => 'required|in:harian,mingguan,bulanan,semester,tahunan',
                'print_mode' => 'required|in:class,student',
                'class_id' => 'nullable|integer|exists:school_classes,id',
                'student_scope' => 'nullable|in:single,class,classes,all',
                'student_id' => 'nullable|integer|exists:students,id',
                'scope_class_id' => 'nullable|integer|exists:school_classes,id',
                'period_type' => 'required|in:harian,mingguan,bulanan,semester,tahunan',
                'period_date' => 'nullable|date',
                'period_month' => 'nullable|integer|between:1,12',
                'period_year' => 'nullable|integer|min:2020|max:2030',
                'period_semester' => 'nullable|in:ganjil,genap',
                'period_academic_year' => 'nullable|integer|exists:academic_years,id',
            ]);

            $format = $validated['format'];
            $type = $validated['type'];
            $printMode = $validated['print_mode'];
            $classId = $validated['class_id'] ?? null;
            $studentScope = $validated['student_scope'] ?? null;
            $studentId = $validated['student_id'] ?? null;
            $scopeClassId = $validated['scope_class_id'] ?? null;
            $periodType = $validated['period_type'];
            $periodDate = $validated['period_date'] ?? null;
            $periodMonth = $validated['period_month'] ?? null;
            $periodYear = $validated['period_year'] ?? null;
            $periodSemester = $validated['period_semester'] ?? null;
            $periodAcademicYear = $validated['period_academic_year'] ?? null;

            $inputs = [
                'date' => $periodDate,
                'month' => $periodMonth,
                'year' => $periodYear,
                'period_semester' => $periodSemester,
                'period_academic_year' => $periodAcademicYear,
            ];

            if ($printMode === 'class') {
                $studentQuery = Student::with('schoolClass')->where('status', 'Aktif');
                if ($classId) {
                    $studentQuery->where('school_class_id', $classId);
                }
                $recap = $this->getRecapData($periodType, $classId, $inputs, false);

                $studentIds = $studentQuery->pluck('id')->toArray();
                $recap['dataRows'] = collect($recap['dataRows'])->filter(function ($row) use ($studentIds) {
                    return in_array($row['student']->id, $studentIds);
                })->values();

                if ($format === 'pdf') {
                    return $this->generatePdfRekap($request, $recap, $format, $classId, $inputs);
                } else {
                    return $this->generateExcelRekap($request, $recap, $format, $classId, $inputs);
                }
            } else {
                $studentQuery = Student::with('schoolClass')->where('status', 'Aktif');

                if ($studentScope === 'single' && $studentId) {
                    $studentQuery->where('id', $studentId);
                } elseif ($studentScope === 'class' && $scopeClassId) {
                    $studentQuery->where('school_class_id', $scopeClassId);
                } elseif ($studentScope === 'classes') {
                    return response()->json([
                        'message' => 'Scope "classes" harus dipecah per kelas oleh browser.',
                    ], 422);
                }

                $students = $studentQuery->orderBy('school_class_id')->orderBy('name')->get();

                if ($students->isEmpty()) {
                    return response()->json(['message' => 'Tidak ada siswa aktif yang ditemukan untuk dicetak.'], 422);
                }

                $now = Carbon::now('Asia/Jakarta');
                $activeYear = AcademicYear::getActive();
                $dateRange = $this->resolveDateRange($periodType, $inputs, $now, $activeYear);
                $periodLabel = $dateRange['label'];

                $recap = $this->getRecapData($periodType, null, $inputs, false);

                $studentIds = $students->pluck('id');
                $filteredDataRows = collect($recap['dataRows'])->filter(function ($row) use ($studentIds) {
                    return $row['student'] && $studentIds->contains($row['student']->id);
                })->values();

                $studentsData = [];
                $no = 1;
                foreach ($filteredDataRows as $row) {
                    $row['no'] = $no++;
                    $studentsData[] = $row;
                }

                if (empty($studentsData)) {
                    foreach ($students as $student) {
                        if ($periodType === 'harian') {
                            $studentsData[] = [
                                'no' => 1,
                                'student' => $student,
                                'check_in' => '-',
                                'check_out' => '-',
                                'late_text' => '-',
                                'status' => 'Belum',
                                'notes' => '-',
                            ];
                        } else {
                            $studentsData[] = [
                                'no' => 1,
                                'student' => $student,
                                'days' => [],
                                'hadir' => 0,
                                'terlambat' => 0,
                                'sakit' => 0,
                                'izin' => 0,
                                'alfa' => 0,
                                'percentage' => 0,
                            ];
                        }
                    }
                }

                if ($format === 'pdf') {
                    return $this->generatePdfStudentProfile($request, $studentsData, $periodType, $periodLabel, $studentScope, $recap);
                } else {
                    return $this->generateExcelStudentProfile($request, $studentsData, $periodType, $periodLabel, $studentScope, $recap);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Gagal cetak rekap: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception' => $e
            ]);
            return response()->json(['message' => 'Gagal membuat file: ' . $e->getMessage()], 500);
        }
    }

    protected function generatePdfRekap(Request $request, array $recap, string $format, ?int $classId, array $inputs)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        Carbon::setLocale('id');

        $type = $recap['type'];
        $selectedClass = $classId ? SchoolClass::with('teacher')->find($classId) : null;
        $className = $classId ? SchoolClass::find($classId)?->name : 'Semua Kelas';

        $fileName = "Rekap_Presensi_{$type}_" . Str::slug($className) . ".pdf";

        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();
        $activeYear = AcademicYear::getActive();

        $sections = MultiPeriodAttendanceExport::buildSections(
            is_object($recap['dataRows']) ? $recap['dataRows']->toArray() : $recap['dataRows'],
            $recap['type'],
            $inputs,
            $classId
        );

        $logoBase64 = $this->getLogoBase64();

        $pdf = Pdf::loadView('admin.attendances.pdf_multi_rekap', array_merge($recap, [
            'title' => "Laporan Presensi {$recap['type']} - {$className}",
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'logoBase64' => $logoBase64,
            'activeYear' => $activeYear,
            'headmasterName' => Setting::getHeadmasterName(),
            'headmasterNip' => Setting::getHeadmasterNip(),
            'sections' => $sections,
        ]))->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    protected function generateExcelRekap(Request $request, array $recap, string $format, ?int $classId, array $inputs)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        $type = $recap['type'];
        $selectedClass = $classId ? SchoolClass::find($classId) : null;
        $className = $classId ? SchoolClass::find($classId)?->name : 'semua_kelas';

        $fileName = "Rekap_Presensi_{$type}_" . Str::slug($className) . ".xlsx";

        return \App\Services\DownloadCacheService::downloadRekapFile('xlsx', $recap['type'], $classId, $inputs, function() use ($type, $classId, $inputs, $className) {
            $response = Excel::download(new MultiPeriodAttendanceExport($type, array_merge($inputs, ['class_id' => $classId])), "Rekap_Presensi_{$type}_" . Str::slug($className) . ".xlsx");
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            return $response;
        }, "Rekap_Presensi_{$type}_" . Str::slug($className) . ".xlsx");
    }

    protected function generatePdfStudentProfile(Request $request, array $studentsData, string $periodType, string $periodLabel, ?string $studentScope, array $recap = [])
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '300');
        @set_time_limit(300);

        Carbon::setLocale('id');

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();
        $headmasterName = Setting::getHeadmasterName();
        $headmasterNip = Setting::getHeadmasterNip();

        $logoBase64 = $this->getLogoBase64();
        $fileName = $this->studentProfileFileName($studentsData, $periodType, $studentScope, 'pdf');

        $sections = [];
        foreach ($studentsData as $data) {
            $student = $data['student'] ?? null;
            if (!$student) {
                continue;
            }
            $className = $student->schoolClass?->name ?? '-';
            $wali = $student->schoolClass?->teacher;

            // Semester/Tahunan: bulan tanpa catatan tampil 0 (sama dengan halaman web),
            // bukan '-'. Bulan di luar rentang tidak ada di $months jadi tidak ikut tampil.
            if (in_array($periodType, ['semester', 'tahunan'], true) && !empty($recap['months'])) {
                $monthlyFilled = $data['monthly'] ?? [];
                foreach ($recap['months'] as $mInfo) {
                    if (!isset($monthlyFilled[$mInfo['key']])) {
                        $monthlyFilled[$mInfo['key']] = 0;
                    }
                }
                $data['monthly'] = $monthlyFilled;
            }

            $rowData = $data;
            unset($rowData['student']);
            $rowData['student'] = $student;

            $sections[] = [
                'class' => $student->schoolClass,
                'className' => $className,
                'rows' => [$rowData],
                'waliName' => $wali?->name,
                'waliNip' => $wali?->nip,
                'heading' => $this->buildStudentProfileHeading($periodType, $recap),
                'subtitle' => $this->buildStudentProfileSubtitle($periodType, $recap, $className, $data),
            ];
        }

        $months = $recap['months'] ?? null;
        $dateColumns = $recap['dateColumns'] ?? [];
        $daysInMonth = $recap['daysInMonth'] ?? null;

        $dateLabels = [];
        if ($periodType === 'mingguan' && !empty($dateColumns)) {
            foreach ($dateColumns as $col) {
                $dateLabels[] = [
                    'date' => $col['date'],
                    'label' => $col['label'] ?? $col['carbon']->translatedFormat('D, d/m'),
                ];
            }
        }

        $firstStudentName = $studentsData[0]['student']['name'] ?? '-';
        $pdf = Pdf::loadView('admin.attendances.pdf_multi_rekap', [
            'title' => "Profil Presensi {$periodType} - {$firstStudentName}",
            'studentsData' => $studentsData,
            'periodType' => $periodType,
            'periodLabel' => $periodLabel,
            'activeYear' => $activeYear,
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'logoBase64' => $logoBase64,
            'headmasterName' => $headmasterName,
            'headmasterNip' => $headmasterNip,
            'printedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i') . ' WIB',
            'sections' => $sections,
            'months' => $months,
            'dateColumns' => $dateColumns,
            'dateLabels' => $dateLabels,
            'daysInMonth' => $daysInMonth,
            'type' => $periodType,
        ])->setPaper('a4', 'landscape');

        $tempDir = storage_path('app/temp/profile_siswa_pdf');
        File::ensureDirectoryExists($tempDir);
        $tempFile = $tempDir . '/profil_' . uniqid('', true) . '.pdf';
        File::put($tempFile, $pdf->output());

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ])->deleteFileAfterSend(true);
    }

    protected function buildStudentProfileHeading(string $periodType, array $recap): string
    {
        Carbon::setLocale('id');
        $schoolName = Setting::getSchoolName();

        if ($periodType === 'semester') {
            $semester = ($recap['period_semester'] ?? 'ganjil') === 'genap' ? 'genap' : 'ganjil';
            return 'LAPORAN REKAPITULASI PRESENSI SEMESTER ' . strtoupper($semester);
        }
        if ($periodType === 'tahunan') {
            return 'LAPORAN REKAPITULASI PRESENSI TAHUNAN';
        }

        $label = strtoupper($periodType);
        return 'LAPORAN REKAPITULASI PRESENSI ' . $label . ' - ' . ($schoolName ?: '-');
    }

    protected function buildStudentProfileSubtitle(string $periodType, array $recap, string $className, array $data): string
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');

        if ($periodType === 'harian') {
            $date = $recap['date'] ?? $now->toDateString();
            $period = 'Tanggal: ' . Carbon::parse($date)->translatedFormat('l, d F Y');
        } elseif ($periodType === 'mingguan') {
            $dateColumns = $recap['dateColumns'] ?? [];
            if (!empty($dateColumns)) {
                $start = Carbon::parse($dateColumns[0]['date']);
                $end = Carbon::parse(end($dateColumns)['date']);
                $period = 'Periode: ' . $start->translatedFormat('d M Y') . ' s/d ' . $end->translatedFormat('d M Y');
            } else {
                $period = 'Periode: -';
            }
        } elseif ($periodType === 'semester' || $periodType === 'tahunan') {
            $startDate = $recap['startDate'] ?? '';
            $endDate = $recap['endDate'] ?? '';
            $period = 'Periode: ' . Carbon::parse($startDate)->translatedFormat('F Y')
                . ' s/d ' . Carbon::parse($endDate)->translatedFormat('F Y');
        } else {
            $month = (int) ($recap['month'] ?? $now->month);
            $year = (int) ($recap['year'] ?? $now->year);
            $period = 'Bulan: ' . Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        }

        $activeYear = AcademicYear::getActive();

        return $period
            . ' | Kelas: ' . $className
            . ' | Tahun Ajaran: ' . ($activeYear ? $activeYear->name : '-');
    }

    protected function generateExcelStudentProfile(Request $request, array $studentsData, string $periodType, string $periodLabel, ?string $studentScope, array $recap = [])
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '300');
        @set_time_limit(300);

        Carbon::setLocale('id');

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        $excelData = $this->transformForExcelExport($studentsData, $periodType, $recap);
        $fileName = $this->studentProfileFileName($studentsData, $periodType, $studentScope, 'xlsx');

        $response = Excel::download(
            new \App\Exports\StudentProfileExport($excelData, $periodType, $periodLabel, $activeYear, $schoolName, $schoolAddress),
            $fileName
        );
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return $response;
    }

    protected function transformForExcelExport(array $studentsData, string $periodType, array $recap): array
    {
        $transformed = [];
        foreach ($studentsData as $data) {
            $student = $data['student'] ?? null;
            if (!$student) {
                continue;
            }

            $rows = [];
            if ($periodType === 'harian') {
                $rows[] = [
                    'tanggal_raw' => $recap['date'] ?? Carbon::now()->toDateString(),
                    'jam_masuk' => $data['check_in'] ?? '-',
                    'jam_pulang' => $data['check_out'] ?? '-',
                    'status' => $data['status'] ?? '-',
                    'keterlambatan' => $data['late_text'] ?? '-',
                    'keterangan' => $data['notes'] ?? '-',
                ];
            } elseif (in_array($periodType, ['mingguan', 'bulanan'], true)) {
                $dateColumns = $recap['dateColumns'] ?? [];
                foreach ($dateColumns as $col) {
                    $code = $data['days'][$col['date']] ?? '-';
                    $rows[] = [
                        'tanggal_raw' => $col['date'],
                        'jam_masuk' => '-',
                        'jam_pulang' => '-',
                        'status' => $this->codeToStatus($code),
                        'keterlambatan' => $code === 'T' ? 'Terlambat' : '-',
                        'keterangan' => '-',
                    ];
                }
            } elseif (in_array($periodType, ['semester', 'tahunan'], true)) {
                $months = $recap['months'] ?? [];
                $monthly = $data['monthly'] ?? [];
                foreach ($months as $monthInfo) {
                    $hadirCount = $monthly[$monthInfo['key']] ?? 0;
                    $rows[] = [
                        'tanggal_raw' => $monthInfo['key'] . '-01',
                        'jam_masuk' => '-',
                        'jam_pulang' => '-',
                        'status' => $hadirCount > 0 ? 'Hadir' : '-',
                        'keterlambatan' => '-',
                        'keterangan' => $hadirCount > 0 ? "{$hadirCount} hari hadir" : '-',
                    ];
                }
            }

            $stats = [
                'hadir' => $data['hadir'] ?? 0,
                'terlambat' => $data['terlambat'] ?? 0,
                'sakit' => $data['sakit'] ?? 0,
                'izin' => $data['izin'] ?? 0,
                'alfa' => $data['alfa'] ?? 0,
                'libur' => 0,
            ];

            $totalEffective = ($data['hadir'] ?? 0) + ($data['terlambat'] ?? 0) + ($data['sakit'] ?? 0) + ($data['izin'] ?? 0) + ($data['alfa'] ?? 0);
            $totalHadir = ($data['hadir'] ?? 0) + ($data['terlambat'] ?? 0);
            $percentage = $data['percentage'] ?? 0;

            $transformed[] = [
                'student' => $student,
                'rows' => $rows,
                'stats' => $stats,
                'totalEffective' => $totalEffective,
                'totalHadir' => $totalHadir,
                'percentage' => $percentage,
            ];
        }

        return $transformed;
    }

    protected function codeToStatus(string $code): string
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

    protected function getLogoBase64(): ?string
    {
        try {
            static $cache = null;
            static $resolved = false;

            if ($resolved) {
                return $cache;
            }
            $resolved = true;

            $relative = Setting::getLogo();
            // Jika logo tidak ada, kembalikan gambar transparan 1x1 piksel base64
            // agar DomPDF tidak pernah error "Cannot resolve public path"
            if (empty($relative) || !is_string($relative)) {
                return $cache = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
            }

            $cleanPath = ltrim(parse_url($relative, PHP_URL_PATH) ?? $relative, '/');
            $cleanPath = str_replace('storage/', '', $cleanPath);

            $possiblePaths = [
                public_path($cleanPath),
                public_path('storage/' . $cleanPath),
                storage_path('app/public/' . $cleanPath),
            ];

            $path = null;
            foreach ($possiblePaths as $testPath) {
                if (is_file($testPath)) {
                    $path = $testPath;
                    break;
                }
            }

            if (!$path) {
                return $cache = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
            }

            $raw = @file_get_contents($path);
            if ($raw === false || $raw === '') {
                return $cache = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
            }

            $max = 120;
            if (function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor')) {
                try {
                    $src = @imagecreatefromstring($raw);
                    if ($src) {
                        $w = imagesx($src);
                        $h = imagesy($src);
                        $scale = min($max / max($w, 1), $max / max($h, 1), 1.0);
                        $nw = max(1, (int) round($w * $scale));
                        $nh = max(1, (int) round($h * $scale));

                        $dst = imagecreatetruecolor($nw, $nh);
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $transparent = imagecolorallocatealpha($dst, 127, 127, 127, 127);
                        imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                        imagedestroy($src);

                        ob_start();
                        imagepng($dst, null, 6);
                        $png = ob_get_clean();
                        imagedestroy($dst);

                        if ($png) {
                            return $cache = 'data:image/png;base64,' . base64_encode($png);
                        }
                    }
                } catch (\Throwable $e) {}
            }

            $mime = @mime_content_type($path) ?: 'image/png';
            return $cache = 'data:' . $mime . ';base64,' . base64_encode($raw);

        } catch (\Throwable $e) {
            return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        }
    }
}
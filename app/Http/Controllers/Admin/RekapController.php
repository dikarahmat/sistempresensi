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
use App\Models\Teacher;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class RekapController extends Controller
{
    /**
     * Dapatkan data rekapitulasi berdasarkan tipe periode (Harian, Mingguan, Bulanan).
     */
    protected function getRecapData(string $type, ?int $classId, array $inputs, bool $paginateStudents = false): array
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $type = in_array($type, ['harian', 'mingguan', 'bulanan']) ? $type : 'bulanan';
        $activeYear = AcademicYear::getActive();

        $query = Student::with('schoolClass');
        if ($classId) {
            $query->where('school_class_id', $classId);
        }
        $studentPaginator = $paginateStudents
            ? $query->orderBy('name', 'asc')->paginate(50)->withQueryString()
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

        if ($type === 'harian') {
            $date = $inputs['date'] ?? $now->toDateString();
            $dateObj = Carbon::parse($date);
            $isSunday = $dateObj->isSunday();
            $isHoliday = Holiday::isHoliday($date) || $isSunday;
            $holidayDesc = Holiday::getHolidayDescription($date) ?? ($isSunday ? 'Hari Minggu' : null);

            $attendances = Attendance::where('date', $date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');

            foreach ($students as $student) {
                $att = $attendances->get($student->id);
                $status = 'Belum Hadir';
                $checkIn = '-';
                $lateMinutes = 0;
                $lateText = '-';
                $notes = '-';
                $proofDoc = null;

                if ($att) {
                    $status = $att->status;
                    $notes = $att->notes ?? '-';
                    $proofDoc = $att->proof_document;

                    if ($status === 'Hadir') {
                        $checkIn = $att->check_in ? substr($att->check_in, 0, 5) . ' WIB' : '-';
                        if ($att->time_remark === 'Terlambat') {
                            $diff = Carbon::parse($att->check_in)->diffInMinutes(Carbon::parse($lateLimitTime), false);
                            $lateMinutes = $diff < 0 ? abs($diff) : 0;
                            $lateText = "+{$lateMinutes}m";
                            $status = 'Terlambat';
                            $totalTerlambat++;
                            $totalHadir++;
                        } else {
                            $lateText = 'Tepat Waktu';
                            $totalHadir++;
                        }
                    } elseif ($status === 'Sakit') {
                        $totalSakit++;
                    } elseif ($status === 'Izin') {
                        $totalIzin++;
                    } elseif ($status === 'Alfa') {
                        $totalAlfa++;
                    }
                } else {
                    if ($isHoliday) {
                        $status = 'Libur';
                    } elseif ($date < $now->toDateString()) {
                        $status = 'Alfa';
                        $totalAlfa++;
                    } elseif ($date === $now->toDateString() && $now->format('H:i') > $lateLimitTime) {
                        $status = 'Alfa';
                        $totalAlfa++;
                    }
                }

                $dataRows[] = [
                    'student' => $student,
                    'check_in' => $checkIn,
                    'late_text' => $lateText,
                    'late_minutes' => $lateMinutes,
                    'status' => $status,
                    'notes' => $notes,
                    'proof_document' => $proofDoc,
                ];
            }

            return compact(
                'type', 'date', 'dateObj', 'isHoliday', 'holidayDesc',
                'students', 'dataRows', 'totalHadir', 'totalTerlambat',
                'totalSakit', 'totalIzin', 'totalAlfa', 'studentPaginator'
            );
        } elseif ($type === 'mingguan') {
            $startDate = $inputs['start_date'] ?? $now->copy()->startOfWeek()->toDateString();
            $endDate = $inputs['end_date'] ?? $now->copy()->startOfWeek()->addDays(4)->toDateString();

            $startObj = Carbon::parse($startDate);
            $endObj = Carbon::parse($endDate);

            $dateColumns = [];
            $cur = $startObj->copy();
            while ($cur->lte($endObj)) {
                $curDateStr = $cur->toDateString();
                $isSun = $cur->isSunday();
                $isHol = Holiday::isHoliday($curDateStr);
                $dateColumns[] = [
                    'date' => $curDateStr,
                    'carbon' => $cur->copy(),
                    'label' => $cur->translatedFormat('D, d/m'),
                    'is_holiday' => $isSun || $isHol,
                ];
                $cur->addDay();
            }

            $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->groupBy('student_id');

            foreach ($students as $student) {
                $stAtts = $attendances->get($student->id, collect())->keyBy('date');
                $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;
                $days = [];

                foreach ($dateColumns as $col) {
                    $dStr = $col['date'];
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
                        if ($col['is_holiday']) {
                            $code = 'L';
                        } elseif ($dStr <= $now->toDateString()) {
                            $code = 'A';
                            $a++;
                        }
                    }

                    $days[$dStr] = $code;
                }

                $totalEffective = count(array_filter($dateColumns, fn($c) => !$c['is_holiday']));
                $percentage = $totalEffective > 0 ? round(($h / $totalEffective) * 100) : 0;

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

            return compact(
                'type', 'startDate', 'endDate', 'startObj', 'endObj',
                'dateColumns', 'students', 'dataRows', 'totalHadir',
                'totalTerlambat', 'totalSakit', 'totalIzin', 'totalAlfa',
                'studentPaginator'
            );
        } else {
            // BULANAN
            $month = (int) ($inputs['month'] ?? $now->month);
            $year = (int) ($inputs['year'] ?? $now->year);
            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            $holidayMap = [];
            $dayDateStrings = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $cDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $dayDateStrings[$d] = $cDate;
                $isSun = Carbon::createFromDate($year, $month, $d)->isSunday();
                $isHol = Holiday::isHoliday($cDate);
                if ($isSun || $isHol) {
                    $holidayMap[$d] = Holiday::getHolidayDescription($cDate) ?? 'Akhir Pekan';
                }
            }

            $startDate = $dayDateStrings[1];
            $endDate = $dayDateStrings[$daysInMonth];
            $nowDateStr = $now->toDateString();

            $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
                ->whereIn('student_id', $students->pluck('id'))
                ->select(['id', 'student_id', 'date', 'status', 'time_remark'])
                ->get()
                ->groupBy('student_id');

            $totalEffective = max(1, $daysInMonth - count($holidayMap));

            foreach ($students as $student) {
                $stAtts = $attendances->get($student->id, collect())->keyBy(function ($item) {
                    return (int) substr($item->date, 8, 2);
                });

                $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;
                $days = [];

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $cDate = $dayDateStrings[$d];
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
                        if (isset($holidayMap[$d])) {
                            $code = 'L';
                        } elseif ($cDate <= $nowDateStr) {
                            $code = 'A';
                            $a++;
                        }
                    }

                    $days[$d] = $code;
                }

                $percentage = round(($h / $totalEffective) * 100);

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

            return compact(
                'type', 'month', 'year', 'daysInMonth', 'holidayMap',
                'students', 'dataRows', 'totalHadir', 'totalTerlambat',
                'totalSakit', 'totalIzin', 'totalAlfa', 'studentPaginator'
            );
        }
    }

    protected function paginateRecapRows(array $recap): array
    {
        $paginator = $recap['studentPaginator'] ?? null;
        if ($paginator instanceof LengthAwarePaginator) {
            $paginator->setCollection(collect($recap['dataRows'] ?? []));
            $recap['dataRows'] = $paginator;
        }

        unset($recap['studentPaginator']);

        return $recap;
    }

    /**
     * Halaman Rekapitulasi Presensi Admin.
     */
    public function index(Request $request): View
    {
        $type = $request->input('type', 'bulanan');
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
        ]));
    }

    /**
     * Ekspor Excel Admin Multi-Periode.
     */
    public function exportExcel(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        $type = $request->input('type', 'bulanan');
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $selectedClass = $classId ? SchoolClass::find($classId) : null;
        $className = $selectedClass ? Str::slug($selectedClass->name) : 'semua_kelas';

        $fileName = "Rekap_Presensi_{$type}_{$className}_" . date('Ymd_His') . ".xlsx";

        return Excel::download(new MultiPeriodAttendanceExport($type, array_merge($request->all(), ['class_id' => $classId])), $fileName);
    }

    /**
     * Ekspor PDF Admin Multi-Periode.
     */
    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        Carbon::setLocale('id');
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        $type = $request->input('type', 'bulanan');
        $classId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        $selectedClass = $classId ? SchoolClass::with('teacher')->find($classId) : null;
        $className = $selectedClass ? $selectedClass->name : 'Semua Kelas';

        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();
        $activeYear = AcademicYear::getActive();

        $recap = $this->getRecapData($type, $classId, $request->all());

        $reportHeading = "LAPORAN REKAPITULASI PRESENSI - " . strtoupper($type);
        if ($type === 'harian') {
            $reportSubheading = "Tanggal: " . Carbon::parse($recap['date'])->translatedFormat('l, d F Y') . " | Kelas: {$className}";
        } elseif ($type === 'mingguan') {
            $reportSubheading = "Periode: " . Carbon::parse($recap['startDate'])->translatedFormat('d M Y') . " s/d " . Carbon::parse($recap['endDate'])->translatedFormat('d M Y') . " | Kelas: {$className}";
        } else {
            $reportSubheading = "Bulan: " . Carbon::createFromDate($recap['year'], $recap['month'], 1)->translatedFormat('F Y') . " | Kelas: {$className}";
        }

        // Ambil logo sekolah jika ada
        $logoPath = Setting::getLogo();
        $logoBase64 = null;
        if (file_exists(public_path($logoPath))) {
            $logoData = file_get_contents(public_path($logoPath));
            $logoBase64 = 'data:image/webp;base64,' . base64_encode($logoData);
        }

        $rightSignatoryTitle = $selectedClass ? 'Wali Kelas ' . $selectedClass->name : 'Petugas Presensi';

        $pdf = Pdf::loadView('admin.attendances.pdf_multi_rekap', array_merge($recap, [
            'title' => "Laporan Presensi {$type} - {$className}",
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'reportHeading' => $reportHeading,
            'reportSubheading' => $reportSubheading,
            'logoBase64' => $logoBase64,
            'activeYear' => $activeYear,
            'headmasterName' => Setting::getHeadmasterName(),
            'headmasterNip' => Setting::getHeadmasterNip(),
            'teacherName' => $selectedClass?->teacher?->name,
            'teacherNip' => $selectedClass?->teacher?->nip,
            'rightSignatoryTitle' => $rightSignatoryTitle,
        ]))->setPaper('a4', 'landscape');

        $fileName = "Rekap_Presensi_{$type}_" . Str::slug($className) . ".pdf";
        return $pdf->download($fileName);
    }

    /**
     * Dapatkan Guru dan Kelas yang dibina oleh user guru yang sedang login.
     */
    protected function getTeacherAndClassForGuru(?Request $request = null): array
    {
        $user = Auth::user();
        if (!$user) {
            return [null, null, null, collect()];
        }

        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher && !empty($user->name)) {
            $teacher = Teacher::where('name', $user->name)
                ->orWhere('name', 'like', '%' . trim($user->name) . '%')
                ->first();
            if ($teacher && empty($teacher->user_id)) {
                $teacher->update(['user_id' => $user->id]);
            }
        }

        $teacherIds = collect();
        if ($teacher) {
            $teacherIds->push($teacher->id);
        }
        $sameNameTeachers = Teacher::where('name', $user->name)->pluck('id');
        $teacherIds = $teacherIds->merge($sameNameTeachers)->unique();

        $activeYear = AcademicYear::getActive();

        $teacherClasses = collect();
        if ($teacherIds->isNotEmpty()) {
            $teacherClasses = SchoolClass::whereIn('teacher_id', $teacherIds)
                ->with(['academicYear', 'teacher'])
                ->orderBy('name')
                ->get();
        }

        $schoolClass = null;
        $requestedClassId = $request 
            ? ($request->input('school_class_id') ?? $request->input('class_id')) 
            : (request('school_class_id') ?? request('class_id'));

        if ($requestedClassId) {
            $schoolClass = $teacherClasses->firstWhere('id', $requestedClassId);
            if (!$schoolClass) {
                abort(403, 'Akses ditolak: Anda tidak memiliki akses ke kelas ini.');
            }
        }

        if (!$schoolClass && $teacherClasses->isNotEmpty()) {
            if ($activeYear) {
                $schoolClass = $teacherClasses->firstWhere('academic_year_id', $activeYear->id);
            }
            if (!$schoolClass) {
                $schoolClass = $teacherClasses->first(function ($c) {
                    return Student::where('school_class_id', $c->id)->exists();
                });
            }
            if (!$schoolClass) {
                $schoolClass = $teacherClasses->first();
            }
        }

        if (!$schoolClass && $teacher) {
            $schoolClass = SchoolClass::where('teacher_id', $teacher->id)->first();
        }

        return [$teacher, $schoolClass, $activeYear, $teacherClasses];
    }

    /**
     * Halaman Rekapitulasi Presensi Guru (Khusus Kelas Binaan).
     */
    public function guruIndex(Request $request): View
    {
        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClassForGuru($request);

        $type = $request->input('type', 'bulanan');
        $resolvedClassId = $schoolClass?->id;

        if (!$resolvedClassId) {
            $recap = [
                'type' => $type,
                'students' => collect(),
                'dataRows' => [],
                'totalHadir' => 0,
                'totalTerlambat' => 0,
                'totalSakit' => 0,
                'totalIzin' => 0,
                'totalAlfa' => 0,
                'month' => (int) $request->input('month', date('n')),
                'year' => (int) $request->input('year', date('Y')),
                'daysInMonth' => 30,
                'holidayMap' => [],
                'date' => date('Y-m-d'),
                'startDate' => date('Y-m-d'),
                'endDate' => date('Y-m-d'),
                'dateColumns' => [],
            ];
        } else {
            $recap = $this->getRecapData($type, $resolvedClassId, $request->all(), true);
            $recap = $this->paginateRecapRows($recap);
        }

        return view('walikelas.attendances.rekap', array_merge($recap, [
            'teacher' => $teacher,
            'schoolClass' => $schoolClass,
            'classes' => $teacherClasses,
            'classId' => $resolvedClassId,
            'activeYear' => $activeYear,
            'type' => $type,
        ]));
    }

    /**
     * Ekspor Excel Guru Multi-Periode.
     */
    public function guruExportExcel(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClassForGuru($request);

        if (!$schoolClass) {
            return back()->with('error', 'Anda belum memiliki kelas yang dibina.');
        }

        $type = $request->input('type', 'bulanan');
        $className = Str::slug($schoolClass->name);
        $fileName = "Rekap_Presensi_{$type}_{$className}_" . date('Ymd_His') . ".xlsx";

        return Excel::download(new MultiPeriodAttendanceExport($type, array_merge($request->all(), ['class_id' => $schoolClass->id])), $fileName);
    }

    /**
     * Ekspor PDF Guru Multi-Periode.
     */
    public function guruExportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

        Carbon::setLocale('id');
        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClassForGuru($request);

        if (!$schoolClass) {
            return back()->with('error', 'Anda belum memiliki kelas yang dibina.');
        }

        $type = $request->input('type', 'bulanan');
        $className = $schoolClass->name;

        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();

        $recap = $this->getRecapData($type, $schoolClass->id, $request->all());

        $reportHeading = "LAPORAN REKAPITULASI PRESENSI KELAS " . strtoupper($className);
        if ($type === 'harian') {
            $reportSubheading = "Tanggal: " . Carbon::parse($recap['date'])->translatedFormat('l, d F Y') . " | Wali Kelas: " . ($teacher?->name ?? '-');
        } elseif ($type === 'mingguan') {
            $reportSubheading = "Periode: " . Carbon::parse($recap['startDate'])->translatedFormat('d M Y') . " s/d " . Carbon::parse($recap['endDate'])->translatedFormat('d M Y') . " | Wali Kelas: " . ($teacher?->name ?? '-');
        } else {
            $reportSubheading = "Bulan: " . Carbon::createFromDate($recap['year'], $recap['month'], 1)->translatedFormat('F Y') . " | Wali Kelas: " . ($teacher?->name ?? '-');
        }

        $logoPath = Setting::getLogo();
        $logoBase64 = null;
        if (file_exists(public_path($logoPath))) {
            $logoData = file_get_contents(public_path($logoPath));
            $logoBase64 = 'data:image/webp;base64,' . base64_encode($logoData);
        }

        $pdf = Pdf::loadView('admin.attendances.pdf_multi_rekap', array_merge($recap, [
            'title' => "Laporan Presensi {$type} - Kelas {$className}",
            'schoolName' => $schoolName,
            'schoolAddress' => $schoolAddress,
            'reportHeading' => $reportHeading,
            'reportSubheading' => $reportSubheading,
            'logoBase64' => $logoBase64,
            'activeYear' => $activeYear ?? AcademicYear::getActive(),
            'headmasterName' => Setting::getHeadmasterName(),
            'headmasterNip' => Setting::getHeadmasterNip(),
            'teacherName' => $teacher?->name,
            'teacherNip' => $teacher?->nip,
            'rightSignatoryTitle' => 'Wali Kelas ' . $className,
        ]))->setPaper('a4', 'landscape');

        $fileName = "Rekap_Presensi_{$type}_Kelas_" . Str::slug($className) . ".pdf";
        return $pdf->download($fileName);
    }
}
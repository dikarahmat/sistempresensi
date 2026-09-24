<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;

use App\Exports\MonthlyAttendanceExport;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class WaliKelasPortalController extends Controller
{
    /**
     * Dapatkan data Guru dan Kelas yang dibina oleh User yang sedang login.
     */
    protected function getTeacherAndClass(?Request $request = null): array
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
     * Dashboard Portal Guru / Wali Kelas.
     */
    public function dashboard(Request $request): View
    {
        Carbon::setLocale('id');
        $today = Carbon::now('Asia/Jakarta');
        $dateString = $today->toDateString();

        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass($request);

        $checkInTime = Setting::getCheckInTime();
        $lateLimitTime = Setting::getLateLimitTime();

        $isSunday = $today->isSunday();
        $isHoliday = Holiday::isHoliday($dateString) || $isSunday;
        $holidayDescription = Holiday::getHolidayDescription($dateString) ?? ($isSunday ? 'Hari Minggu (Akhir Pekan)' : null);

        $totalStudents = 0;
        $countHadir = 0;
        $countTerlambat = 0;
        $countSakit = 0;
        $countIzin = 0;
        $countAlfa = 0;
        $countBelumHadir = 0;
        $recentAttendances = collect();

        if ($schoolClass) {
            $students = Student::where('school_class_id', $schoolClass->id)
                ->with(['attendances' => fn($q) => $q->where('date', $dateString)])
                ->orderBy('name')
                ->get();


            $totalStudents = $students->count();

            foreach ($students as $student) {
                $att = $student->attendances->first();
                if ($att) {
                    if ($att->status === 'Hadir') {
                        if ($att->time_remark === 'Terlambat') {
                            $countTerlambat++;
                            $countHadir++;
                        } else {
                            $countHadir++;
                        }
                    } elseif ($att->status === 'Sakit') {
                        $countSakit++;
                    } elseif ($att->status === 'Izin') {
                        $countIzin++;
                    } elseif ($att->status === 'Alfa') {
                        $countAlfa++;
                    }
                } else {
                    if ($isHoliday) {
                        // Hari libur tidak dihitung alpha
                    } elseif ($today->format('H:i') > $lateLimitTime) {
                        $countAlfa++;
                    } else {
                        $countBelumHadir++;
                    }
                }
            }

            $recentAttendances = Attendance::with('student')
                ->whereHas('student', fn($q) => $q->where('school_class_id', $schoolClass->id))
                ->where('date', $dateString)
                ->whereNotNull('check_in')
                ->latest('updated_at')
                ->take(10)
                ->get();
        }

        $percentageHadir = ($totalStudents > 0) ? round(($countHadir / $totalStudents) * 100) : 0;
        $totalKetidakhadiran = $countSakit + $countIzin + $countAlfa;

        // Alias penamaan variabel untuk kompatibilitas view
        $totalSiswa = $totalStudents;
        $hadirHariIni = $countHadir;
        $sakitHariIni = $countSakit;
        $izinHariIni = $countIzin;
        $alphaHariIni = $countAlfa;
        $belumHadirHariIni = $countBelumHadir;

        $todayFormatted = $today->translatedFormat('l, d F Y');
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek = $today->copy()->toDateString();
        $checkOutTime = Setting::getCheckOutTime();

        // Data Grafik Tren Kehadiran Mingguan Khusus Kelas Binaan
        $chartLabels = [];
        $chartData = [];

        if ($schoolClass && $totalStudents > 0) {
            $startMonday = $today->copy()->startOfWeek(Carbon::MONDAY);

            for ($i = 0; $i < 5; $i++) {
                $dayDate = $startMonday->copy()->addDays($i);
                $dayDateString = $dayDate->toDateString();
                $dayName = $dayDate->translatedFormat('l');

                $chartLabels[] = $dayName . ' (' . $dayDate->format('d/m') . ')';

                if ($dayDateString <= $dateString) {
                    $hadirDay = Attendance::whereHas('student', fn($q) => $q->where('school_class_id', $schoolClass->id))
                        ->whereDate('date', $dayDateString)
                        ->where('status', 'Hadir')
                        ->count();

                    $pct = min(100, round(($hadirDay / $totalStudents) * 100));
                    $chartData[] = $pct;
                } else {
                    $chartData[] = null;
                }
            }
        }

        return view('walikelas.dashboard', compact(
            'teacher',
            'schoolClass',
            'activeYear',
            'today',
            'todayFormatted',
            'dateString',
            'startOfWeek',
            'endOfWeek',
            'checkInTime',
            'lateLimitTime',
            'checkOutTime',
            'isHoliday',
            'holidayDescription',
            'totalStudents',
            'totalSiswa',
            'countHadir',
            'hadirHariIni',
            'countTerlambat',
            'countSakit',
            'sakitHariIni',
            'countIzin',
            'izinHariIni',
            'countAlfa',
            'alphaHariIni',
            'countBelumHadir',
            'belumHadirHariIni',
            'totalKetidakhadiran',
            'percentageHadir',
            'recentAttendances',
            'teacherClasses',
            'chartLabels',
            'chartData'
        ));
    }

    /**
     * Halaman Presensi Wali Kelas (Tersinkronisasi dengan Model Admin).
     */
    public function index(Request $request): View
    {
        return app(AttendanceController::class)->index($request);
    }

    /**
     * Detail Presensi Kelas Binaan.
     */
    public function showClass(Request $request, SchoolClass|int $schoolClass): View
    {
        return app(AttendanceController::class)->showClass($request, $schoolClass);
    }

    /**
     * Halaman Scanner Presensi Masuk (Khusus Kelas Guru - Nonaktif/Redirect).
     */
    public function scanner()
    {
        return app(ScannerController::class)->index();
    }

    /**
     * Proses Scan QR Masuk (Validasi Siswa Hanya di Kelas Binaan).
     */
    public function processScan(Request $request): JsonResponse
    {
        return app(ScannerController::class)->store($request);
    }

    /**
     * Halaman Input Presensi Manual & Monitoring Harian Kelas Guru.
     */
    public function dailyAttendance(Request $request)
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass($request);
        $teacherClassIds = $teacherClasses->pluck('id')->toArray();

        $academicYears = AcademicYear::orderByDesc('is_active')->orderByDesc('start_date')->get();
        $activeYear = AcademicYear::getActive() ?? $academicYears->first();
        $selectedYearId = $request->input('academic_year_id', $activeYear?->id);
        $search = trim($request->input('search', ''));

        // Query Rombel/Kelas HANYA untuk kelas binaan guru
        $classesQuery = SchoolClass::whereIn('id', $teacherClassIds)
            ->with(['teacher', 'academicYear'])
            ->withCount(['students as total_students' => fn($q) => $q->where('status', 'Aktif')]);

        if ($search !== '') {
            $classesQuery->where('name', 'like', "%{$search}%");
        }

        $classes = $classesQuery->orderBy('grade')->orderBy('name')->get();

        // Ambil data absensi hari ini khusus siswa kelas binaan
        $activeStudents = Student::where('status', 'Aktif')
            ->whereIn('school_class_id', $teacherClassIds)
            ->select(['id', 'school_class_id'])
            ->get();

        $studentIds = $activeStudents->pluck('id');
        $activeStudentsByClass = $activeStudents->groupBy('school_class_id');

        $todayAttendances = Attendance::where('date', $today)
            ->whereIn('student_id', $studentIds)
            ->select(['id', 'student_id', 'status'])
            ->get()
            ->keyBy('student_id');

        $classHistories = $classes->map(function ($cls) use ($todayAttendances, $activeStudentsByClass) {
            $classStudents = $activeStudentsByClass->get($cls->id, collect());
            $studentIds = $classStudents->pluck('id');
            $totalStudents = (int) ($cls->total_students ?? $classStudents->count());

            $todayHadir = 0;
            $todayRecorded = 0;
            foreach ($studentIds as $sid) {
                $att = $todayAttendances->get($sid);
                if ($att) {
                    $todayRecorded++;
                    if ($att->status === 'Hadir') {
                        $todayHadir++;
                    }
                }
            }

            $hasTodayAttendance = $todayHadir > 0;
            $persentaseToday = ($totalStudents > 0 && $todayHadir > 0)
                ? round(($todayHadir / $totalStudents) * 100)
                : 0;

            return (object) [
                'id' => $cls->id,
                'name' => $cls->name,
                'grade' => $cls->grade,
                'teacher' => $cls->teacher ? $cls->teacher->name : 'Belum ditentukan',
                'academic_year' => $cls->academicYear ? $cls->academicYear->name : '-',
                'total_students' => $totalStudents,
                'today_hadir' => $todayHadir,
                'today_recorded' => $todayRecorded,
                'has_today_attendance' => $hasTodayAttendance,
                'persentase' => $persentaseToday,
            ];
        });

        $totalKelas = $classHistories->count();
        $maxHari = 0;
        $totalSiswaSemua = $classHistories->sum('total_students');
        $classesWithAtt = $classHistories->filter(fn($c) => $c->has_today_attendance);
        $avgPersentase = $classesWithAtt->count() > 0 ? round($classesWithAtt->avg('persentase')) : 0;

        $props = [
            'classHistories' => $classHistories,
            'academicYears' => $academicYears,
            'selectedYearId' => $selectedYearId,
            'activeYear' => $activeYear,
            'search' => $search,
            'totalKelas' => $totalKelas,
            'maxHari' => $maxHari,
            'totalSiswaSemua' => $totalSiswaSemua,
            'avgPersentase' => $avgPersentase,
            'teacher' => $teacher,
            'schoolClass' => $schoolClass,
            'teacherClasses' => $teacherClasses,
            'classes' => $teacherClasses,
        ];

        return view('walikelas.attendances.kehadiran', $props);
    }

    /**
     * Override / Input Presensi Manual (Sakit / Izin / Alfa / Hadir).
     */
    public function overrideAttendance(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'status' => 'required|in:Hadir,Terlambat,Sakit,Izin,Alfa',
            'check_in' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
            'proof_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        [$teacher, $schoolClass, $activeYear] = $this->getTeacherAndClass();

        $student = Student::findOrFail($request->student_id);

        if (!$schoolClass || $student->school_class_id !== $schoolClass->id) {
            return back()->with('error', 'Akses ditolak! Siswa ini bukan siswa di kelas binaan Anda.');
        }

        $status = $request->status;
        $timeRemark = null;

        if ($status === 'Terlambat') {
            $status = 'Hadir';
            $timeRemark = 'Terlambat';
        } elseif ($status === 'Hadir') {
            $timeRemark = 'Tepat Waktu';
        }

        $attendance = Attendance::firstOrNew([
            'student_id' => $student->id,
            'date' => $request->date,
        ]);

        if (!$attendance->exists) {
            $attendance->academic_year_id = $activeYear?->id;
        }

        $attendance->status = $status;
        $attendance->time_remark = $timeRemark;
        if ($request->filled('check_in')) {
            $attendance->check_in = $request->check_in;
        }
        $attendance->notes = $request->notes;

        if ($request->hasFile('proof_document')) {
            if ($attendance->proof_document && Storage::disk('public')->exists($attendance->proof_document)) {
                Storage::disk('public')->delete($attendance->proof_document);
            }
            $attendance->proof_document = $request->file('proof_document')->store('attendance_proofs', 'public');
        }

        $attendance->save();

        return back()->with('success', 'Status presensi siswa ' . $student->name . ' berhasil diperbarui!');
    }

    /**
     * Daftar Siswa di Kelas Bimbingan Guru Saja.
     */
    public function students(Request $request): View
    {
        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass($request);

        $students = collect();
        if ($schoolClass) {
            $query = Student::where('school_class_id', $schoolClass->id)->with('schoolClass');

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            }

            $students = $query->orderBy('name', 'asc')->paginate(50)->withQueryString();
        }

        return view('walikelas.students.index', compact('teacher', 'schoolClass', 'activeYear', 'teacherClasses', 'students'));
    }

    /**
     * Download Kartu Presensi Siswa (PDF Resmi).
     */
    public function downloadCard(Student $student)
    {
        [$teacher, $schoolClass] = $this->getTeacherAndClass();

        if (!$schoolClass || $student->school_class_id !== $schoolClass->id) {
            abort(403, 'Akses ditolak! Siswa bukan bagian dari kelas Anda.');
        }

        $schoolName = Setting::getSchoolName();
        $schoolAddress = Setting::getSchoolAddress();
        $activeYear = AcademicYear::getActive();
        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $student->load('schoolClass');
        $students = collect([$student]);

        $pdf = Pdf::loadView('shared.print-cards', array_merge(compact('students', 'schoolName', 'schoolAddress', 'activeYear', 'logoBase64'), ['isPdf' => true]))
            ->setPaper('a4', 'portrait');

        $fileName = 'Kartu_Pelajar_' . $student->nis . '_' . Str::slug($student->name) . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Rekap Presensi Bulanan Kelas yang Diampu.
     */
    public function monthlyRecap(Request $request): View
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $month = (int) $request->input('month', $now->month);
        $year = (int) $request->input('year', $now->year);

        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass($request);

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $currentDateString = $now->toDateString();

        $holidayMap = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $cDate = Carbon::createFromDate($year, $month, $d)->toDateString();
            $isSun = Carbon::createFromDate($year, $month, $d)->isSunday();
            $isHol = Holiday::isHoliday($cDate);
            if ($isSun || $isHol) {
                $holidayMap[$d] = Holiday::getHolidayDescription($cDate) ?? 'Akhir Pekan';
            }
        }

        $students = collect();
        $recapData = [];
        $totalHadirSemua = 0;
        $totalTerlambatSemua = 0;
        $totalSakitSemua = 0;
        $totalIzinSemua = 0;
        $totalAlfaSemua = 0;

        if ($schoolClass) {
            $students = Student::where('school_class_id', $schoolClass->id)
                ->orderBy('name', 'asc')
                ->get();


            $startDate = Carbon::createFromDate($year, $month, 1)->toDateString();
            $endDate = Carbon::createFromDate($year, $month, $daysInMonth)->toDateString();

            $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->groupBy('student_id');

            foreach ($students as $student) {
                $studentAtts = $attendances->get($student->id, collect())->keyBy(function ($item) {
                    return Carbon::parse($item->date)->day;
                });

                $h = 0; $t = 0; $s = 0; $i = 0; $a = 0;
                $days = [];

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $dayDateStr = Carbon::createFromDate($year, $month, $d)->toDateString();
                    $isPastOrToday = $dayDateStr <= $currentDateString;
                    $isHoliday = isset($holidayMap[$d]);

                    if ($studentAtts->has($d)) {
                        $att = $studentAtts->get($d);
                        if ($att->status === 'Hadir') {
                            if ($att->time_remark === 'Terlambat') {
                                $days[$d] = 'T';
                                $t++;
                                $h++;
                            } else {
                                $days[$d] = 'H';
                                $h++;
                            }
                        } elseif ($att->status === 'Sakit') {
                            $days[$d] = 'S';
                            $s++;
                        } elseif ($att->status === 'Izin') {
                            $days[$d] = 'I';
                            $i++;
                        } elseif ($att->status === 'Alfa') {
                            $days[$d] = 'A';
                            $a++;
                        }
                    } else {
                        if ($isHoliday) {
                            $days[$d] = 'L';
                        } elseif ($isPastOrToday) {
                            $days[$d] = 'A';
                            $a++;
                        } else {
                            $days[$d] = '-';
                        }
                    }
                }

                $totalEffectiveDays = $daysInMonth - count($holidayMap);
                $percentage = $totalEffectiveDays > 0 ? round(($h / $totalEffectiveDays) * 100) : 0;

                $totalHadirSemua += $h;
                $totalTerlambatSemua += $t;
                $totalSakitSemua += $s;
                $totalIzinSemua += $i;
                $totalAlfaSemua += $a;

                $recapData[] = [
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
        }

        return view('walikelas.attendances.rekap', compact(
            'teacher',
            'schoolClass',
            'month',
            'year',
            'daysInMonth',
            'holidayMap',
            'recapData',
            'totalHadirSemua',
            'totalTerlambatSemua',
            'totalSakitSemua',
            'totalIzinSemua',
            'totalAlfaSemua'
        ));
    }

    /**
     * Ekspor Rekap Bulanan Kelas Guru ke Excel.
     */
    public function exportExcel(Request $request)
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $month = (int) $request->input('month', $now->month);
        $year = (int) $request->input('year', $now->year);

        [$teacher, $schoolClass] = $this->getTeacherAndClass();

        if (!$schoolClass) {
            return back()->with('error', 'Anda belum memiliki kelas yang dibina untuk diekspor.');
        }

        $className = Str::slug($schoolClass->name);
        $fileName = "Rekap_Presensi_{$className}_{$month}_{$year}.xlsx";

        return Excel::download(new MonthlyAttendanceExport($month, $year, $schoolClass->id), $fileName);
    }
}

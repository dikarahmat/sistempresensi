<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\View\View;

class KesiswaanDashboardController extends Controller
{
    /**
     * Tampilkan Dashboard Real-Time Kesiswaan.
     * Bersifat strictly Read-Only.
     */
    public function index(): View
    {
        Carbon::setLocale('id');
        $today = Carbon::now('Asia/Jakarta');
        $dateString = $today->toDateString();
        $todayFormatted = $today->translatedFormat('l, d F Y');

        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek = $today->copy()->toDateString();

        $activeYear = AcademicYear::getActive();

        // 1. Data Statistik Header & Master Kelas (Single Query Eager Loading)
        $totalStudents = Student::where('status', 'Aktif')->count();

        $classesList = SchoolClass::with('teacher')
            ->withCount(['students as total_students' => fn($q) => $q->where('status', 'Aktif')])
            ->orderBy('grade')
            ->orderBy('name')
            ->get();
        $totalClasses = $classesList->count();
        $totalTeachers = Teacher::count();
        $totalWaliKelas = $classesList->whereNotNull('teacher_id')->unique('teacher_id')->count();

        // 2. Data Presensi Hari Ini
        $attendancesToday = Attendance::with(['student.schoolClass'])
            ->where('date', $dateString)
            ->when($activeYear, fn($q) => $q->where('academic_year_id', $activeYear->id))
            ->get();

        $countHadir = $attendancesToday->where('status', 'Hadir')->count();
        $countSakit = $attendancesToday->where('status', 'Sakit')->count();
        $countIzin  = $attendancesToday->where('status', 'Izin')->count();
        $countAlfa  = $attendancesToday->filter(fn($a) => in_array($a->status, ['Alfa', 'Alpha']))->count();
        $totalKetidakhadiran = $countSakit + $countIzin + $countAlfa;

        // Terlambat & Tepat Waktu
        $countTerlambat = $attendancesToday->where('status', 'Hadir')->where('time_remark', 'Terlambat')->count();
        $countTepatWaktu = $countHadir - $countTerlambat;

        // Persentase
        $percentageHadir = $totalStudents > 0 ? round(($countHadir / $totalStudents) * 100, 1) : 0;
        $percentageSakit = $totalStudents > 0 ? round(($countSakit / $totalStudents) * 100, 1) : 0;
        $percentageIzin  = $totalStudents > 0 ? round(($countIzin / $totalStudents) * 100, 1) : 0;
        $percentageAlfa  = $totalStudents > 0 ? round(($countAlfa / $totalStudents) * 100, 1) : 0;

        // 3. Data Grafik Batang / Garis Analitik Kehadiran Mingguan (Agregasi Single Batch Query)
        $effectiveDays = Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])
            ->distinct('date')
            ->count('date');
        $effectiveDays = max(1, $effectiveDays);

        $weeklyHadirCounts = Attendance::join('students', 'attendances.student_id', '=', 'students.id')
            ->where('students.status', 'Aktif')
            ->whereBetween('attendances.date', [$startOfWeek, $endOfWeek])
            ->where('attendances.status', 'Hadir')
            ->groupBy('students.school_class_id')
            ->selectRaw('students.school_class_id, count(*) as total_hadir')
            ->pluck('total_hadir', 'students.school_class_id');

        $classesAttendance = $classesList->map(function ($cls) use ($weeklyHadirCounts, $effectiveDays) {
            $weeklyHadir = (int) ($weeklyHadirCounts->get($cls->id) ?? 0);
            $totalCapacity = ($cls->total_students ?? 0) * $effectiveDays;

            $cls->percentage = $totalCapacity > 0 
                ? min(100, round(($weeklyHadir / $totalCapacity) * 100))
                : 0;
            $cls->weekly_hadir = $weeklyHadir;
            $cls->total_capacity = $totalCapacity;

            return $cls;
        });

        // 4. Distribusi Kehadiran per Kelas Hari Ini (In-Memory Grouping anti N+1)
        $attendancesByClass = $attendancesToday->groupBy(fn($att) => $att->student?->school_class_id);

        $classesBreakdown = $classesList->map(function ($cls) use ($attendancesByClass) {
                $clsAtts = $attendancesByClass->get($cls->id, collect());

                $cls->hadir = $clsAtts->where('status', 'Hadir')->count();
                $cls->sakit = $clsAtts->where('status', 'Sakit')->count();
                $cls->izin = $clsAtts->where('status', 'Izin')->count();
                $cls->alfa = $clsAtts->where('status', 'Alfa')->count();
                $cls->terlambat = $clsAtts->where('status', 'Hadir')->where('time_remark', 'Terlambat')->count();
                $cls->percentage = $cls->total_students > 0 ? round(($cls->hadir / $cls->total_students) * 100) : 0;

                return $cls;
            });

        // 4. Log Presensi Terkini Hari Ini (10 Data Terakhir)
        $recentAttendances = Attendance::with(['student.schoolClass'])
            ->where('date', $dateString)
            ->whereNotNull('check_in')
            ->orderBy('check_in', 'desc')
            ->take(10)
            ->get();

        if ($recentAttendances->isEmpty()) {
            $recentAttendances = Attendance::with(['student.schoolClass'])
                ->where('date', $dateString)
                ->latest('updated_at')
                ->take(10)
                ->get();
        }

        // 5. Parameter Jam Masuk Sekolah dari Cache Teroptimasi
        $checkInTime = Setting::getCheckInTime();
        $lateLimitTime = Setting::getLateLimitTime();

        // 6. Daftar Siswa Terlambat Hari Ini (Diurutkan dari menit keterlambatan tertinggi)
        try {
            $lateLimitCarbon = Carbon::createFromFormat('H:i', $lateLimitTime, 'Asia/Jakarta')
                ->setDate($today->year, $today->month, $today->day);
        } catch (\Exception $e) {
            $lateLimitCarbon = Carbon::createFromFormat('H:i', '07:15', 'Asia/Jakarta')
                ->setDate($today->year, $today->month, $today->day);
        }

        $lateStudentsToday = $attendancesToday->filter(function ($att) use ($lateLimitTime) {
            if ($att->status !== 'Hadir') {
                return false;
            }
            if ($att->time_remark === 'Terlambat') {
                return true;
            }
            if (!empty($att->check_in)) {
                $timeString = substr($att->check_in, 0, 5);
                return $timeString > $lateLimitTime;
            }
            return false;
        })->map(function ($att) use ($lateLimitCarbon, $today) {
            $minutesLate = 0;
            if (!empty($att->check_in)) {
                try {
                    $checkInStr = strlen($att->check_in) === 5 ? $att->check_in . ':00' : $att->check_in;
                    $checkInCarbon = Carbon::createFromFormat('H:i:s', $checkInStr, 'Asia/Jakarta')
                        ->setDate($today->year, $today->month, $today->day);
                    if ($checkInCarbon->greaterThan($lateLimitCarbon)) {
                        $minutesLate = (int) $lateLimitCarbon->diffInMinutes($checkInCarbon);
                    }
                } catch (\Exception $e) {
                    $minutesLate = 0;
                }
            }
            $att->minutes_late = $minutesLate > 0 ? $minutesLate : 1;
            return $att;
        })->sortByDesc('minutes_late')->values()->take(10);

        // 7. Ringkasan Statistik Gabungan
        $todayStats = compact(
            'countHadir',
            'countSakit',
            'countIzin',
            'countAlfa',
            'countTerlambat',
            'countTepatWaktu',
            'percentageHadir',
            'percentageSakit',
            'percentageIzin',
            'percentageAlfa'
        );

        $checkOutTime = Setting::getCheckOutTime();

        return view('kesiswaan.dashboard', compact(
            'today',
            'todayFormatted',
            'dateString',
            'startOfWeek',
            'endOfWeek',
            'activeYear',
            'totalStudents',
            'totalClasses',
            'totalTeachers',
            'totalWaliKelas',
            'classesList',
            'countHadir',
            'countSakit',
            'countIzin',
            'countAlfa',
            'totalKetidakhadiran',
            'countTerlambat',
            'countTepatWaktu',
            'percentageHadir',
            'percentageSakit',
            'percentageIzin',
            'percentageAlfa',
            'classesAttendance',
            'classesBreakdown',
            'recentAttendances',
            'lateStudentsToday',
            'todayStats',
            'checkInTime',
            'lateLimitTime',
            'checkOutTime'
        ));
    }
}

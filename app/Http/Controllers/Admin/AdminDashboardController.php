<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
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
        
        // Ambil data rombel lengkap beserta wali kelas dan jumlah siswa aktif dalam 1 query
        $classesList = SchoolClass::with('teacher')
            ->withCount(['students as total_students' => fn($q) => $q->where('status', 'Aktif')])
            ->orderBy('grade')
            ->orderBy('name')
            ->get();
        $totalClasses = $classesList->count();

        // Mapping Wali Kelas dari koleksi yang sudah dieager-load
        $waliKelasList = $classesList->map(function ($cls) {
            return (object) [
                'class_id' => $cls->id,
                'class_name' => $cls->name,
                'teacher_name' => $cls->teacher ? $cls->teacher->name : 'Belum Ditentukan',
                'teacher_id' => $cls->teacher_id,
                'has_teacher' => !is_null($cls->teacher_id),
            ];
        });

        $totalWaliKelas = $classesList->whereNotNull('teacher_id')->unique('teacher_id')->count();
        $totalTeachers = Teacher::count();

        // 2. Data Ketidakhadiran Hari Ini (Sakit, Izin, Alpha)
        $attendancesToday = Attendance::whereDate('date', $dateString)
            ->when($activeYear, fn($q) => $q->where('academic_year_id', $activeYear->id))
            ->get();

        $countSakit = $attendancesToday->where('status', 'Sakit')->count();
        $countIzin  = $attendancesToday->where('status', 'Izin')->count();
        $countAlfa  = $attendancesToday->filter(fn($a) => in_array($a->status, ['Alfa', 'Alpha']))->count();
        $totalKetidakhadiran = $countSakit + $countIzin + $countAlfa;

        // 3. Data Grafik Batang Analitik Kehadiran Mingguan (Agregasi Single Batch Query anti N+1)
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

        // 4. Pengaturan Jadwal Operasional dari Cache Teroptimasi
        $checkInTime = Setting::getCheckInTime();
        $lateLimitTime = Setting::getLateLimitTime();
        $checkOutTime = Setting::getCheckOutTime();

        return view('admin.dashboard', compact(
            'today', 'todayFormatted', 'dateString', 'startOfWeek', 'endOfWeek',
            'checkInTime', 'lateLimitTime', 'checkOutTime',
            'totalStudents', 'totalClasses', 'totalWaliKelas', 'totalTeachers',
            'classesList', 'waliKelasList',
            'countSakit', 'countIzin', 'countAlfa', 'totalKetidakhadiran',
            'classesAttendance'
        ));
    }
}

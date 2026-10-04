<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Tanggal yang sedang dipilih user lewat date picker di header.
     * Fallback ke hari ini bila parameter kosong / tidak valid.
     */
    private function resolveSelectedDate(Request $request, Carbon $today): Carbon
    {
        $raw = $request->query('tanggal');

        if (filled($raw)) {
            try {
                $parsed = Carbon::createFromFormat('Y-m-d', (string) $raw)->startOfDay();
                if ($parsed->format('Y-m-d') === Carbon::parse((string) $raw)->format('Y-m-d')) {
                    return $parsed;
                }
            } catch (\Throwable $e) {
                // Format tidak valid -> pakai hari ini
            }
        }

        return $today->copy();
    }

    public function index(Request $request): View
    {
        Carbon::setLocale('id');
        $today = Carbon::now('Asia/Jakarta');

        // === 1. TANGGAL PILIHAN (date picker di header) ===
        $selectedDate = $this->resolveSelectedDate($request, $today);
        $dateString = $selectedDate->toDateString();
        $isToday = $selectedDate->isSameDay($today);
        $todayFormatted = $selectedDate->translatedFormat('l, d F Y');
        // Label kecil menyesuaikan isi: "Hari Ini" hanya sah bila memang hari ini.
        $dateLabel = $isToday ? 'Tanggal Hari Ini' : 'Tanggal Dipilih';
        
        $startOfWeek = $selectedDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek = $dateString;

        $activeYear = AcademicYear::getActive();

        // 1. Data Statistik Header & Master Kelas (Single Query Eager Loading)
        $totalStudents = Student::where('status', 'Aktif')->count();
        
        // Ambil data rombel lengkap beserta guru kelasnya dan jumlah siswa aktif dalam 1 query
        $classesList = SchoolClass::with('teacher')
            ->withCount(['students as total_students' => fn($q) => $q->where('status', 'Aktif')])
            ->orderBy('grade')
            ->orderBy('name')
            ->get();
        $totalClasses = $classesList->count();

        // Mapping guru kelas dari koleksi yang sudah dieager-load
        $guruKelasList = $classesList->map(function ($cls) {
            return (object) [
                'class_id' => $cls->id,
                'class_name' => $cls->name,
                'teacher_name' => $cls->teacher ? $cls->teacher->name : 'Belum Ditentukan',
                'teacher_id' => $cls->teacher_id,
                'has_teacher' => !is_null($cls->teacher_id),
            ];
        });

        $totalGuruKelas = $classesList->whereNotNull('teacher_id')->unique('teacher_id')->count();
        $totalTeachers = Teacher::count();

        // 2. Data Ketidakhadiran Hari Ini (Sakit, Izin, Alpha)
        $attendancesToday = Attendance::whereDate('date', $dateString)
            ->when($activeYear, fn($q) => $q->where('academic_year_id', $activeYear->id))
            ->get();

        $countSakit = $attendancesToday->where('status', 'Sakit')->count();
        $countIzin  = $attendancesToday->where('status', 'Izin')->count();
        $countAlfa  = $attendancesToday->filter(fn($a) => in_array($a->status, ['Alfa', 'Alpha']))->count();
        $totalKetidakhadiran = $countSakit + $countIzin + $countAlfa;

        // 3. Grafik Kehadiran - 3 MODE: harian / mingguan / bulanan
        //
        // Default = 'mingguan' karena itu tampilan yang sudah ada sebelumnya,
        // sehingga user tidak melihat perubahan mendadak saat membuka dashboard.
        $chartMode = (string) $request->query('mode', 'mingguan');
        if (! in_array($chartMode, ['harian', 'mingguan', 'bulanan'], true)) {
            $chartMode = 'mingguan';
        }

        // --- 3a. MODE MINGGUAN (TIDAK DIUBAH: per-rombel, sama seperti sebelumnya)
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

        // --- 3b. MODE HARIAN: satu titik per hari dalam minggu terpilih
        // Kapasitas memakai jumlah siswa aktif di seluruh rombel * jumlah hari efektif,
        // sehingga persentasenya sebanding dengan mode mingguan.
        $totalActiveStudents = (int) $classesList->sum('total_students');
        $weeklyDays = max(1, $effectiveDays);

        $dailyHadirCounts = Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])
            ->where('status', 'Hadir')
            ->selectRaw('date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $chartLabels = [];
        $chartValues = [];

        if ($chartMode === 'harian') {
            for ($d = Carbon::parse($startOfWeek); $d->lte(Carbon::parse($endOfWeek)); $d->addDay()) {
                $key = $d->toDateString();
                $hadir = (int) ($dailyHadirCounts->get($key) ?? 0);
                $kapasitas = $totalActiveStudents * $weeklyDays;

                $chartLabels[] = $d->translatedFormat('D, d M');
                $chartValues[] = $kapasitas > 0
                    ? min(100, round(($hadir / $kapasitas) * 100))
                    : 0;
            }
        } elseif ($chartMode === 'bulanan') {
            // --- 3c. MODE BULANAN: satu titik per bulan (12 bulan terakhir).
            // Dipilih "per bulan" (bukan per hari dalam 1 bulan) karena tabel
            // attendances hanya punya kolom `date` tanpa jam. Agregasi per bulan
            // tetap bermakna walau hanya ada 1-2 catatan per hari, sedangkan
            // titik per hari dalam sebulan akan banyak bernilai 0 sehingga
            // garis grafik didominasi angka nol tanpa informasi berguna.
            $bulanIni = $selectedDate->copy()->startOfMonth();
            $bulanAwal = $selectedDate->copy()->subMonths(11)->startOfMonth();

            $monthlyHadirCounts = Attendance::whereBetween('date', [$bulanAwal->toDateString(), $bulanIni->copy()->endOfMonth()->toDateString()])
                ->where('status', 'Hadir')
                ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym, count(*) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym');

            for ($m = $bulanAwal->copy(); $m->lte($bulanIni); $m->addMonth()) {
                $hadir = (int) ($monthlyHadirCounts->get($m->format('Y-m')) ?? 0);
                // Kapasitas: siswa aktif x jumlah hari efektif di bulan tersebut.
                $hariEfektif = max(1, (int) $m->daysInMonth);
                $kapasitas = $totalActiveStudents * $hariEfektif;

                $chartLabels[] = $m->translatedFormat('M Y');
                $chartValues[] = $kapasitas > 0
                    ? min(100, round(($hadir / $kapasitas) * 100))
                    : 0;
            }
        } else {
            // Mingguan: label & data per-rombel (persis seperti sebelumnya).
            $chartLabels = $classesAttendance->pluck('name')->toArray();
            $chartValues = $classesAttendance->pluck('percentage')->toArray();
        }

        $totalChartPoints = count($chartValues);

        // Apakah periode yang sedang ditampilkan benar-benar punya catatan
        // presensi? Mode harian/bulanan SELALU menghasilkan titik (7 hari / 12
        // bulan) walau datanya nol, sehingga tanpa penanda ini kartu tidak
        // pernah menampilkan "Belum ada data" dan hanya menampilkan garis
        // rata di angka 0. Mode mingguan memakai periode yang sama.
        $periodeChart = ($chartMode === 'bulanan')
            ? [$selectedDate->copy()->subMonths(11)->startOfMonth()->toDateString(),
               $selectedDate->copy()->endOfMonth()->toDateString()]
            : [$startOfWeek, $endOfWeek];

        $chartHasData = Attendance::whereBetween('date', $periodeChart)->exists();

        // Teks-teks yang menyesuaikan mode (judul kartu, periode, total, kosong).
        // Nilai default = mode MINGGUAN, sehingga tampilan saat mode mingguan
        // identik dengan sebelumnya.
        if ($chartMode === 'harian') {
            $chartTitle = 'Grafik Garis Kehadiran Harian';
            $chartPeriodLabel = Carbon::parse($startOfWeek)->translatedFormat('d M')
                . ' - ' . Carbon::parse($endOfWeek)->translatedFormat('d M Y');
            $chartTotalText = 'Total: ' . $totalChartPoints . ' Hari';
            $chartScopeText = 'Per hari dalam minggu ini';
            $chartEmptyText = 'Belum ada data kehadiran pada periode ini.';
        } elseif ($chartMode === 'bulanan') {
            $chartTitle = 'Grafik Garis Kehadiran Bulanan';
            $chartPeriodLabel = Carbon::parse($selectedDate)->copy()->subMonths(11)->translatedFormat('M Y')
                . ' - ' . Carbon::parse($selectedDate)->translatedFormat('M Y');
            $chartTotalText = 'Total: ' . $totalChartPoints . ' Bulan';
            $chartScopeText = 'Per bulan, 12 bulan terakhir';
            $chartEmptyText = 'Belum ada data kehadiran pada periode 12 bulan terakhir.';
        } else {
            $chartTitle = 'Grafik Garis Kehadiran Mingguan';
            $chartPeriodLabel = Carbon::parse($startOfWeek)->translatedFormat('d M')
                . ' - ' . Carbon::parse($endOfWeek)->translatedFormat('d M Y');
            $chartTotalText = 'Total: ' . count($classesAttendance ?? []) . ' Rombel';
            $chartScopeText = ($classesList->first()->name ?? '7A') . ' s/d ' . ($classesList->last()->name ?? '9C');
            $chartEmptyText = 'Belum ada data kehadiran rombel pada minggu ini.';
        }

        // 4. Pengaturan Jadwal Operasional dari Cache Teroptimasi
        $checkInTime = Setting::getCheckInTime();
        $lateLimitTime = Setting::getLateLimitTime();
        $checkOutTime = Setting::getCheckOutTime();

        return view('admin.dashboard', compact(
            'today', 'todayFormatted', 'dateString', 'startOfWeek', 'endOfWeek',
            'isToday', 'dateLabel', 'selectedDate',
            'checkInTime', 'lateLimitTime', 'checkOutTime',
            'totalStudents', 'totalClasses', 'totalGuruKelas', 'totalTeachers',
            'classesList', 'guruKelasList',
            'countSakit', 'countIzin', 'countAlfa', 'totalKetidakhadiran',
            'classesAttendance',
            // Data grafik (sudah dihitung sesuai mode di atas)
            'chartMode', 'chartLabels', 'chartValues', 'totalChartPoints', 'chartHasData',
            'chartTitle', 'chartPeriodLabel', 'chartTotalText', 'chartScopeText', 'chartEmptyText'
        ));
    }
}

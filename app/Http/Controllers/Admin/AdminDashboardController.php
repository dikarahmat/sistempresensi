<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DailyAttendanceSummary;
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

    public function index(Request $request, DailyAttendanceSummary $dailyAttendanceSummary)
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
        
        // $startOfWeek dan $endOfWeek ditahan untuk bagian "REKAP MINGGUAN PER
        // ROMBEL" (bukan grafik). Grafik kesehatan kini pakai rentang mandiri
        // dari todayJakarta supaya tidak terpengaruh tanggal yang dipilih user.
        $startOfWeek = $selectedDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endOfWeek   = $dateString;

        $activeYear = AcademicYear::getActive();

        // 1. Data Statistik Header & Master Kelas (Single Query Eager Loading)
        //
        // Angka "Siswa Aktif" HARUS sama persis dengan total yang tampil di
        // halaman Data Siswa. Halaman itu memakai Student::with('schoolClass')
        // TANPA filter status, jadi di sini juga tanpa filter status - query
        // global SoftDeletes otomatis mengecualikan siswa yang sudah dihapus
        // (masuk sampah). Query yang sama dipakai untuk roster rombel di bawah.
        $totalStudents = Student::count();
        
        // Ambil data rombel lengkap beserta guru kelasnya dan jumlah siswa aktif dalam 1 query
        // (query dasar sama dengan SchoolClassController@index, jadi angka
        // "Rombongan Belajar" = total di halaman Data Kelas; yang di sampah
        // tidak terhitung karena global scope SoftDeletes).
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
        // "Guru Aktif" = total di halaman Data Guru: query yang sama
        // (Teacher::with('schoolClass') tanpa filter status). Guru yang sudah
        // dihapus (masuk sampah) tidak terhitung karena global scope SoftDeletes.
        $totalTeachers = Teacher::count();

        // 2. Data Ketidakhadiran (Sakit, Izin, Alfa) untuk tanggal yang
        //    sedang dipilih. Angkanya diambil dari DailyAttendanceSummary -
        //    helper yang sama dipakai halaman Presensi (daftar kelas & detail
        //    kelas), jadi "Alfa" di Dashboard sama persis dengan Alfa di
        //    halaman presensi (termasuk Alfa hasil hitungan untuk tanggal
        //    yang sudah lewat).
        $kelasSummary = $dailyAttendanceSummary->summarize($classesList, $dateString);

        $countSakit = (int) $kelasSummary->sum('sakit');
        $countIzin  = (int) $kelasSummary->sum('izin');
        $countAlfa  = (int) $kelasSummary->sum('alpha');
        $totalKetidakhadiran = $countSakit + $countIzin + $countAlfa;

        // 3. REKAP MINGGUAN PER ROMBEL - data pendukung dashboard. Tetap
        //    dihitung agar variabel $classesAttendance tidak hilang dari data
        //    view (dipakai test regresi & bisa dipakai dashboard berikutnya).
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

        $chartMode = (string) $request->query('mode', 'harian');
        if (! in_array($chartMode, ['harian', 'mingguan', 'bulanan'], true)) {
            $chartMode = 'harian';
        }

        // --------------------------------------------------------------------
        // GRAFIK: satu sumber data per mode, semua pakai timezone Asia/Jakarta.
        // - Harian  : hari ini saja (1 titik).
        // - Mingguan: 7 hari terakhir termasuk hari ini (7 titik, per tanggal).
        // - Bulanan : bulan berjalan saja (1 titik per tanggal dari 1 s.d. akhir
        //             bulan), tanpa 12 bulan terakhir.
        // --------------------------------------------------------------------
        $todayJakarta = Carbon::now('Asia/Jakarta');
        $totalActiveStudents = (int) $classesList->sum('total_students');

        $chartLabels = [];
        $chartValues = [];

        if ($chartMode === 'harian') {
            // HARI INI SAJA.
            $periodStart = $todayJakarta->copy()->startOfDay();
            $periodEnd   = $todayJakarta->copy()->endOfDay();

            $dailyHadirCounts = Attendance::whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->where('status', 'Hadir')
                ->selectRaw('date, count(*) as total')
                ->groupBy('date')
                ->pluck('total', 'date');

            $hadir     = (int) ($dailyHadirCounts->get($periodStart->toDateString()) ?? 0);
            $kapasitas = $totalActiveStudents;

            $chartLabels[] = $periodStart->translatedFormat('d M Y');
            $chartValues[] = $kapasitas > 0
                ? min(100, round(($hadir / $kapasitas) * 100))
                : 0;
        } elseif ($chartMode === 'mingguan') {
            // 7 HARI TERAKHIR TERMASUK HARI INI (MON-SUN jika hari ini belum
            // weekend, dst). Sumbu X = per tanggal/hari.
            $periodStart = $todayJakarta->copy()->subDays(6)->startOfDay();
            $periodEnd   = $todayJakarta->copy()->endOfDay();

            $dailyHadirCounts = Attendance::whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->where('status', 'Hadir')
                ->selectRaw('date, count(*) as total')
                ->groupBy('date')
                ->pluck('total', 'date');

            for ($d = $periodStart->copy(); $d->lte($periodEnd); $d->addDay()) {
                $key = $d->toDateString();
                $hadir     = (int) ($dailyHadirCounts->get($key) ?? 0);
                $kapasitas = $totalActiveStudents;

                $chartLabels[] = $d->translatedFormat('d M');
                $chartValues[] = $kapasitas > 0
                    ? min(100, round(($hadir / $kapasitas) * 100))
                    : 0;
            }
        } else {
            // BULANAN: bulan berjalan SAJA (1 s.d. akhir bulan), sumbu X per
            // tanggal. 12 bulan terakhir TIDAK lagi ditampilkan.
            $periodStart = $todayJakarta->copy()->startOfMonth();
            $periodEnd   = $todayJakarta->copy()->endOfMonth();

            $dailyHadirCounts = Attendance::whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->where('status', 'Hadir')
                ->selectRaw('date, count(*) as total')
                ->groupBy('date')
                ->pluck('total', 'date');

            for ($d = $periodStart->copy(); $d->lte($periodEnd); $d->addDay()) {
                $key = $d->toDateString();
                $hadir     = (int) ($dailyHadirCounts->get($key) ?? 0);
                $kapasitas = $totalActiveStudents;

                $chartLabels[] = $d->translatedFormat('d M');
                $chartValues[] = $kapasitas > 0
                    ? min(100, round(($hadir / $kapasitas) * 100))
                    : 0;
            }
        }

        $totalChartPoints = count($chartValues);

        // Apakah periode yang sedang ditampilkan punya catatan presensi?
        $periodeChart = match ($chartMode) {
            'harian'   => [$todayJakarta->copy()->startOfDay()->toDateString(),
                           $todayJakarta->copy()->endOfDay()->toDateString()],
            'mingguan' => [$todayJakarta->copy()->subDays(6)->startOfDay()->toDateString(),
                           $todayJakarta->copy()->endOfDay()->toDateString()],
            default    => [$todayJakarta->copy()->startOfMonth()->toDateString(),
                           $todayJakarta->copy()->endOfMonth()->toDateString()],
        };

        $chartHasData = Attendance::whereBetween('date', $periodeChart)->exists();

        // Teks pendukung (judul kartu, periode, total, kosong) disesuaikan
        // dengan rentang yang baru.
        $formatPeriod =
            app()->getLocale() === 'en'
                ? fn(Carbon $c) => $c->locale('en')->translatedFormat('d M')
                : fn(Carbon $c) => $c->translatedFormat('d M');

        if ($chartMode === 'harian') {
            $chartTitle       = 'Grafik Garis Kehadiran Harian';
            $chartPeriodLabel = $todayJakarta->translatedFormat('d M Y');
            $chartTotalText   = 'Total: ' . $totalChartPoints . ' Hari';
            $chartScopeText   = 'Hari ini';
            $chartEmptyText   = 'Belum ada data kehadiran hari ini.';
        } elseif ($chartMode === 'bulanan') {
            $chartTitle       = 'Grafik Garis Kehadiran Bulanan';
            $chartPeriodLabel = $todayJakarta->translatedFormat('M Y');
            $chartTotalText   = 'Total: ' . $totalChartPoints . ' Hari';
            $chartScopeText   = 'Per hari, bulan ' . $todayJakarta->translatedFormat('M') . ' ini';
            $chartEmptyText   = 'Belum ada data kehadiran pada bulan ini.';
        } else {
            $chartTitle       = 'Grafik Garis Kehadiran Mingguan';
            $chartPeriodLabel = $formatPeriod($periodStart) . ' - ' . $formatPeriod($periodEnd);
            $chartTotalText   = 'Total: ' . $totalChartPoints . ' Hari';
            $chartScopeText   = 'Per hari, 7 hari terakhir';
            $chartEmptyText   = 'Belum ada data kehadiran pada 7 hari terakhir.';
        }

        // 4. Pengaturan Jadwal Operasional dari Cache Teroptimasi
        $checkInTime = Setting::getCheckInTime();
        $lateLimitTime = Setting::getLateLimitTime();

        return view('admin.dashboard', compact(
            'today', 'todayFormatted', 'dateString', 'startOfWeek', 'endOfWeek',
            'isToday', 'dateLabel', 'selectedDate',
            'checkInTime', 'lateLimitTime',
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

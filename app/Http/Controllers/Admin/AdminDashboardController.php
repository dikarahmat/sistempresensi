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
     * Status presensi yang dihitung "hadir di sekolah" untuk grafik per rombel.
     * Siswa yang Terlambat tetap datang, jadi ikut dihitung. Kalau ingin
     * grafik hanya menghitung status Hadir, ubah jadi ['Hadir'].
     */
    private const STATUS_HADIR_GRAFIK = ['Hadir', 'Terlambat'];

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

        // 2. Data Kehadiran (Hadir, Terlambat, Sakit, Izin, Alfa) untuk tanggal
        //    yang sedang dipilih.
        //    - Sakit / Izin / Alfa diambil dari DailyAttendanceSummary - helper
        //      yang sama dipakai halaman Presensi (daftar kelas & detail
        //      kelas), jadi "Alfa" di Dashboard sama persis dengan Alfa di
        //      halaman presensi (termasuk Alfa hasil hitungan untuk tanggal
        //      yang sudah lewat).
        //    - Hadir & Terlambat dihitung langsung dari tabel presensi pada
        //      tanggal terpilih, hanya untuk siswa aktif yang belum dihapus.
        $kelasSummary = $dailyAttendanceSummary->summarize($classesList, $dateString);

        $countSakit = (int) $kelasSummary->sum('sakit');
        $countIzin  = (int) $kelasSummary->sum('izin');
        $countAlfa  = (int) $kelasSummary->sum('alpha');
        $totalKetidakhadiran = $countSakit + $countIzin + $countAlfa;

        $statusHariIni = Attendance::join('students', 'attendances.student_id', '=', 'students.id')
            ->where('students.status', 'Aktif')
            ->whereNull('students.deleted_at')
            ->whereDate('attendances.date', $dateString)
            ->whereIn('attendances.status', ['Hadir', 'Terlambat'])
            ->groupBy('attendances.status')
            ->selectRaw('attendances.status as st, count(*) as total')
            ->pluck('total', 'st');

        $countHadir     = (int) ($statusHariIni->get('Hadir') ?? 0);
        $countTerlambat = (int) ($statusHariIni->get('Terlambat') ?? 0);

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
        // GRAFIK: SATU GARIS, sumbu X = ROMBEL (7A, 7B, 7C, ...), tinggi titik =
        // persen kehadiran rombel itu pada periode yang dipilih (akumulasi).
        // - Harian  : hari ini.
        // - Mingguan: 7 hari terakhir termasuk hari ini (diakumulasikan).
        // - Bulanan : bulan berjalan (diakumulasikan).
        // Persen rombel = total kehadiran siswa aktif rombel itu selama periode
        //                 / (jumlah siswa aktif rombel x jumlah hari efektif).
        // Hari efektif = jumlah tanggal pada periode yang punya catatan presensi
        // (hari libur / akhir pekan tanpa presensi tidak ikut dihitung).
        // Semua pakai timezone Asia/Jakarta.
        // --------------------------------------------------------------------
        $todayJakarta = Carbon::now('Asia/Jakarta');

        if ($chartMode === 'harian') {
            $periodStart = $todayJakarta->copy()->startOfDay();
            $periodEnd   = $todayJakarta->copy()->endOfDay();
        } elseif ($chartMode === 'mingguan') {
            $periodStart = $todayJakarta->copy()->subDays(6)->startOfDay();
            $periodEnd   = $todayJakarta->copy()->endOfDay();
        } else {
            $periodStart = $todayJakarta->copy()->startOfMonth();
            $periodEnd   = $todayJakarta->copy()->endOfMonth();
        }

        $rentangMulai = $periodStart->toDateString();
        $rentangAkhir = $periodEnd->toDateString();

        $hariEfektifGrafik = max(1, (int) Attendance::whereBetween('date', [$rentangMulai, $rentangAkhir])
            ->distinct('date')
            ->count('date'));

        // Satu query untuk seluruh rombel: total kehadiran per rombel selama periode.
        $hadirPerRombel = Attendance::join('students', 'attendances.student_id', '=', 'students.id')
            ->where('students.status', 'Aktif')
            ->whereNull('students.deleted_at')
            ->whereBetween('attendances.date', [$rentangMulai, $rentangAkhir])
            ->whereIn('attendances.status', self::STATUS_HADIR_GRAFIK)
            ->groupBy('students.school_class_id')
            ->selectRaw('students.school_class_id as class_id, count(*) as total')
            ->pluck('total', 'class_id');

        $chartLabels = $classesList->pluck('name')->values()->all();
        $chartValues = $classesList->map(function ($cls) use ($hadirPerRombel, $hariEfektifGrafik) {
            $kapasitasRombel = (int) ($cls->total_students ?? 0) * $hariEfektifGrafik;
            $hadirRombel     = (int) ($hadirPerRombel->get($cls->id) ?? 0);

            return $kapasitasRombel > 0
                ? min(100, round(($hadirRombel / $kapasitasRombel) * 100))
                : 0;
        })->values()->all();

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
        // dengan rentang yang baru. "Total" sekarang = jumlah rombel (garis).
        $formatPeriod =
            app()->getLocale() === 'en'
                ? fn(Carbon $c) => $c->locale('en')->translatedFormat('d M')
                : fn(Carbon $c) => $c->translatedFormat('d M');

        $chartTotalText = 'Total: ' . $totalClasses . ' Rombel';

        if ($chartMode === 'harian') {
            $chartTitle       = 'Grafik Garis Kehadiran Harian';
            $chartPeriodLabel = $todayJakarta->translatedFormat('d M Y');
            $chartScopeText   = 'Per rombel, hari ini';
            $chartEmptyText   = 'Belum ada data kehadiran hari ini.';
        } elseif ($chartMode === 'bulanan') {
            $chartTitle       = 'Grafik Garis Kehadiran Bulanan';
            $chartPeriodLabel = $todayJakarta->translatedFormat('M Y');
            $chartScopeText   = 'Per rombel, bulan ' . $todayJakarta->translatedFormat('M') . ' ini';
            $chartEmptyText   = 'Belum ada data kehadiran pada bulan ini.';
        } else {
            $chartTitle       = 'Grafik Garis Kehadiran Mingguan';
            $chartPeriodLabel = $formatPeriod($periodStart) . ' - ' . $formatPeriod($periodEnd);
            $chartScopeText   = 'Per rombel, 7 hari terakhir';
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
            'countHadir', 'countTerlambat',
            'countSakit', 'countIzin', 'countAlfa', 'totalKetidakhadiran',
            'classesAttendance',
            // Data grafik (sudah dihitung sesuai mode di atas)
            'chartMode', 'chartLabels', 'chartValues', 'totalChartPoints', 'chartHasData',
            'chartTitle', 'chartPeriodLabel', 'chartTotalText', 'chartScopeText', 'chartEmptyText'
        ));
    }
}
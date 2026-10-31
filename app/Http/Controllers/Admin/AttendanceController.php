<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DailyAttendanceSummary;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Halaman Utama Absensi Harian (support Blade view dan respons JSON REST)
     */
    public function index(Request $request, DailyAttendanceSummary $dailyAttendanceSummary)
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $hariIni = $now->toDateString();
        $tanggal = $request->input('tanggal', $request->input('date', $hariIni));
        $classId = $request->input('class_id');

        if ($request->filled('class_id') && !$request->has('tab')) {
            $cls = SchoolClass::find($request->class_id);
            if ($cls) {
                return $this->showClass($request, $cls);
            }
        }

        $selectedDateObj = Carbon::parse($tanggal);
        $isWeekend = $selectedDateObj->isWeekend();
        $isHoliday = Holiday::isHoliday($tanggal) || $isWeekend;
        $holidayDescription = Holiday::getHolidayDescription($tanggal) ?? ($isWeekend ? 'Akhir Pekan (Sabtu/Minggu)' : null);

        $jamMasuk = Setting::getCheckInTime();
        $batasTerlambat = Setting::getLateLimitTime();

        $formattedJamMasuk = strlen($jamMasuk) === 5 ? $jamMasuk . ':00' : $jamMasuk;
        // check_out_time masih ada di DB untuk logika Scanner, tapi tidak ditampilkan di UI
        $formattedJamPulang = null;

        // Data profil sekolah & pengaturan operasional
        $schoolInfo = [
            'nama_sekolah' => Setting::getSchoolName(),
            'app_title' => Setting::getAppTitle(),
            'logo' => Setting::getLogo(),
            'jam_masuk' => $formattedJamMasuk,
            'batas_terlambat' => $batasTerlambat,
        ];

        // Optimasi Performa Tinggi (Batch Query O(1) in-memory lookup)
        $classes = SchoolClass::with(['teacher'])
            ->withCount(['students as total_siswa' => fn($q) => $q->where('status', 'Aktif')])
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        $kelasList = $dailyAttendanceSummary->summarize($classes, $tanggal);

        // Hitung ringkasan total tingkat sekolah
        $totalSiswaSekolah = (int) $kelasList->sum('total_siswa');
        $totalSudahAbsen = (int) $kelasList->sum('sudah_absen');
        $totalBelumAbsen = max(0, $totalSiswaSekolah - $totalSudahAbsen);
        $totalHadir = (int) $kelasList->sum('hadir');
        $totalTerlambat = (int) $kelasList->sum('terlambat');
        $totalSakit = (int) $kelasList->sum('sakit');
        $totalIzin = (int) $kelasList->sum('izin');
        $totalAlpha = (int) $kelasList->sum('alpha');

        // Query detail siswa hanya jika filter kelas atau status aktif
        $processedStudents = collect();
        if ($classId || $request->filled('status')) {
            $querySiswa = Student::where('status', 'Aktif')->with([
                'schoolClass',
                'attendances' => fn($q) => $q->where('date', $tanggal),
            ]);
            if ($classId) {
                $querySiswa->where('school_class_id', $classId);
            }
            $students = $querySiswa->orderBy('name')->get();

            $processedStudents = $students->map(function ($student) use ($tanggal, $isHoliday, $batasTerlambat) {
                $att = $student->attendances->first();
                $timeRemark = null;
                $lateMinutes = 0;
                $checkIn = null;
                $checkOut = null;
                $notes = null;
                $proofDocument = null;

                if ($att) {
                    $timeRemark = $att->time_remark;
                    $checkIn = $att->check_in;
                    $checkOut = $att->check_out;
                    $notes = $att->notes;
                    $proofDocument = $att->proof_document;

                    // Sama seperti Presensi Kelas: status yang ditampilkan
                    // memakai accessor effective_status supaya "Terlambat"
                    // tidak tampil sebagai "Hadir".
                    $effectiveStatus = $att->effective_status;

                    if ($effectiveStatus === 'Terlambat') {
                        $lateMinutes = (int) ($att->late_minutes ?? 0);
                        if ($lateMinutes === 0 && $checkIn) {
                            $lateMinutes = (int) abs(Carbon::parse($checkIn)
                                ->diffInMinutes(Carbon::parse($batasTerlambat), false));
                        }
                    }
                }

                /*
                 * Tanpa catatan presensi: tanggal lampau -> ALFA (hitungan
                 * tampilan), hari ini / masa depan -> BELUM, hari libur /
                 * akhir pekan -> LIBUR. Sama dengan halaman detail kelas.
                 */
                $status = Attendance::statusOf($att, $tanggal, $isHoliday);

                $student->current_status = $status;
                $student->current_time_remark = $timeRemark;
                $student->late_minutes = $lateMinutes;
                $student->check_in_time = $checkIn;
                $student->check_out_time = $checkOut;
                $student->attendance_notes = $notes;
                $student->proof_document = $proofDocument;
                $student->attendance_id = $att?->id;

                return $student;
            });

            $statusFilter = $request->input('status');
            if ($request->filled('status')) {
                $targetStatus = strtolower($statusFilter);
                $processedStudents = $processedStudents->filter(function ($student) use ($targetStatus) {
                    $cur = strtolower($student->current_status ?? '');
                    if ($targetStatus === 'alfa' || $targetStatus === 'alpha') {
                        return in_array($cur, ['alfa', 'alpha']);
                    }
                    if ($targetStatus === 'tidak_hadir') {
                        return in_array($cur, ['sakit', 'izin', 'alfa', 'alpha']);
                    }
                    return $cur === $targetStatus;
                })->values();
            }
        }

        $selectedClass = $classId ? SchoolClass::find($classId) : null;

        // Props terstruktur untuk Blade view dan respons JSON
        $props = [
            'tanggal' => $tanggal,
            'hariIni' => $hariIni,
            'kelasList' => $kelasList,
            'total_siswa' => $totalSiswaSekolah,
            'sudah_absen' => $totalSudahAbsen,
            'belum_absen' => $totalBelumAbsen,
            'jam_masuk' => $formattedJamMasuk,
            'jam_pulang' => $formattedJamPulang,
            'batas_terlambat' => $batasTerlambat,
            'hadir_count' => $totalHadir,
            'terlambat_count' => $totalTerlambat,
            'sakit_count' => $totalSakit,
            'izin_count' => $totalIzin,
            'alpha_count' => $totalAlpha,
            'is_holiday' => $isHoliday,
            'holiday_description' => $holidayDescription,
            'sekolah' => $schoolInfo,
            'class_id' => $classId,
            'selected_class' => $selectedClass,
            'processed_students' => $processedStudents,
        ];

        // 1. Dukungan JSON jika request via REST / API
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($props);
        }

        // 2. Render Blade View Standar
        $classSummaries = $kelasList->map(function ($item) {
            return (object) [
                'id' => $item['id'],
                'name' => $item['nama_kelas'],
                'grade' => $item['tingkat'],
                'teacher' => (object) ['name' => $item['guru_kelas']],
                'total_students' => $item['total_siswa'],
                'sudah_absen' => $item['sudah_absen'],
                'belum_absen' => $item['belum'],
                'hadir_count' => $item['hadir'],
                'terlambat_count' => $item['terlambat'],
                'sakit_count' => $item['sakit'],
                'izin_count' => $item['izin'],
                'alfa_count' => $item['alpha'],
                'percentage' => $item['persentase'],
            ];
        });

        return view('admin.attendances.daily', array_merge($props, [
            'date' => $tanggal,
            'classes' => SchoolClass::orderBy('name')->get(),
            'classSummaries' => $classSummaries,
            'totalStudents' => $totalSiswaSekolah,
            'countSudahAbsen' => $totalSudahAbsen,
            'countBelumAbsen' => $totalBelumAbsen,
            'countHadir' => $totalHadir + $totalTerlambat,
            'countTerlambat' => $totalTerlambat,
            'countSakit' => $totalSakit,
            'countIzin' => $totalIzin,
            'countAlfa' => $totalAlpha,
            'checkInTime' => $formattedJamMasuk,
            'checkOutTime' => $formattedJamPulang,
            'lateLimitTime' => $batasTerlambat,
            'formattedCheckIn' => $formattedJamMasuk,
            'formattedCheckOut' => $formattedJamPulang,
            'processedStudents' => $processedStudents,
            'classId' => $classId,
            'selectedClass' => $selectedClass,
            'isHoliday' => $isHoliday,
            'holidayDescription' => $holidayDescription,
            'activeTab' => $request->input('tab', $classId ? 'detail' : 'rekap_kelas'),
        ]));
    }

    /**
     * Halaman Detail Absensi Kelas (/admin/absensi/{id} atau /admin/absensi?class_id={id})
     */
    public function showClass(Request $request, SchoolClass|int $schoolClass, DailyAttendanceSummary $dailyAttendanceSummary)
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $hariIni = $now->toDateString();

        if (!($schoolClass instanceof SchoolClass)) {
            $schoolClass = SchoolClass::findOrFail($schoolClass);
        }

        $schoolClass->load(['teacher', 'academicYear']);

        $tanggal = $request->input('tanggal', $request->input('date', $hariIni));
        $selectedDateObj = Carbon::parse($tanggal);
        $isWeekend = $selectedDateObj->isWeekend();
        $isHoliday = Holiday::isHoliday($tanggal) || $isWeekend;
        $holidayDescription = Holiday::getHolidayDescription($tanggal) ?? ($isWeekend ? 'Akhir Pekan (Sabtu/Minggu)' : null);

        $jamMasuk = Setting::getCheckInTime();
        $batasTerlambat = Setting::getLateLimitTime();

        $formattedCheckIn = strlen($jamMasuk) === 5 ? $jamMasuk . ':00' : $jamMasuk;
        // check_out_time masih ada di DB untuk logika Scanner, tapi tidak ditampilkan di UI
        $formattedCheckOut = null;

        // Ambil seluruh siswa aktif di kelas ini
        $students = Student::where('school_class_id', $schoolClass->id)
            ->where('status', 'Aktif')
            ->with(['attendances' => fn($q) => $q->where('date', $tanggal)])
            ->orderBy('name')
            ->get();

        /*
         * ANGKA RINGKASAN KELAS diambil dari SATU sumber yang sama dengan
         * halaman daftar kelas (/admin/absensi): DailyAttendanceSummary.
         * Jadi ringkasan di halaman ini, baris kelas di daftar kelas, badge
         * atas, dan Dashboard dijamin angkanya sama.
         *
         * Aturan hitungnya (sama dengan Rekap & Dashboard):
         *   hadir  = catatan Hadir yang TEPAT WAKTU
         *   terlambat = catatan Hadir yang TERLAMBAT (masih dihitung hadir)
         *   sakit / izin / alfa = catatan dengan status tersebut
         *   belum  = total siswa aktif - (hadir + terlambat + sakit + izin + alfa)
         */
        $ringkasan = $dailyAttendanceSummary->summarize(collect([$schoolClass]), $tanggal)->first() ?? [];

        $studentsList = $students->map(function ($student) use (
            $tanggal, $isHoliday, $batasTerlambat
        ) {
            $att = $student->attendances->first();
            $row = \App\Models\Attendance::mapStudentAttendanceToRow($att, $tanggal, $isHoliday, $batasTerlambat, $student);
            $row->photo = $student->photo ? asset('storage/' . $student->photo) : null;
            return $row;
        });

        $totalSiswa = (int) ($ringkasan['total_siswa'] ?? $studentsList->count());
        $countHadirTepat = (int) ($ringkasan['hadir'] ?? 0);
        $countTerlambat = (int) ($ringkasan['terlambat'] ?? 0);
        $countSakit = (int) ($ringkasan['sakit'] ?? 0);
        $countIzin = (int) ($ringkasan['izin'] ?? 0);
        $countAlfa = (int) ($ringkasan['alpha'] ?? 0);
        $countHadir = $countHadirTepat + $countTerlambat;
        $countSudahAbsen = (int) ($ringkasan['sudah_absen'] ?? ($countHadir + $countSakit + $countIzin + $countAlfa));
        $countBelumAbsen = (int) ($ringkasan['belum'] ?? max(0, $totalSiswa - $countSudahAbsen));

        $props = [
            'selectedClass' => $schoolClass,
            'tanggal' => $tanggal,
            'hariIni' => $hariIni,
            'isHoliday' => $isHoliday,
            'holidayDescription' => $holidayDescription,
            'jamMasuk' => $formattedCheckIn,
            'jamPulang' => $formattedCheckOut,
            'batasTerlambat' => $batasTerlambat,
            'formattedCheckIn' => $formattedCheckIn,
            'formattedCheckOut' => $formattedCheckOut,
            'totalSiswa' => $totalSiswa,
            'countHadir' => $countHadirTepat,
            'countTotalHadir' => $countHadir,
            'countTerlambat' => $countTerlambat,
            'countSakit' => $countSakit,
            'countIzin' => $countIzin,
            'countAlfa' => $countAlfa,
            'countSudahAbsen' => $countSudahAbsen,
            'countBelumAbsen' => $countBelumAbsen,
            'studentsList' => $studentsList,
            'allClasses' => SchoolClass::orderBy('name')->get(),
        ];

        return view('admin.absensi.class', $props);
    }

    /**
     * Halaman Catatan Kehadiran (Histori/Rekap per Kelas) - /admin/kehadiran
     */
    public function kehadiran(Request $request)
    {
        Carbon::setLocale('id');
        $academicYears = AcademicYear::orderByDesc('is_active')->orderByDesc('start_date')->get();
        $activeYear = AcademicYear::getActive() ?? $academicYears->first();
        $selectedYearId = $request->input('academic_year_id', $activeYear?->id);
        $search = trim($request->input('search', ''));

        // Query Rombel/Kelas
        $classesQuery = SchoolClass::with(['teacher', 'academicYear'])
            ->withCount(['students as total_students' => fn($q) => $q->where('status', 'Aktif')]);

        if ($selectedYearId && $request->has('academic_year_id')) {
            $classesQuery->where(function ($q) use ($selectedYearId) {
                $q->where('academic_year_id', $selectedYearId)->orWhereNull('academic_year_id');
            });
        }

        // CATATAN (Perbaikan 2026-10-03): $search TIDAK lagi memfilter daftar
        // kelas. Sebelumnya kelas ikut disaring dengan kata pencarian, sehingga
        // begitu user mengetik nama siswa, opsi dropdown kelas menyusut dan
        // hanya menyisakan "Semua Kelas" sehingga harus menekan RESET.
        // Sekarang daftar kelas selalu lengkap; pencarian hanya untuk tabel.
        $classes = $classesQuery->orderBy('grade')->orderBy('name')->get();

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Ambil data absensi hari ini sekaligus secara efisien (in-memory lookup O(1))
        $todayAttendances = Attendance::where('date', $today)
            ->select(['id', 'student_id', 'status'])
            ->get()
            ->keyBy('student_id');

        // Ambil data siswa aktif yang dipetakan per kelas
        $activeStudentsByClass = Student::where('status', 'Aktif')
            ->select(['id', 'school_class_id', 'name', 'nisn'])
            ->get()
            ->groupBy('school_class_id');

        // Hitung histori kumulatif & presensi hari ini per rombel
        // SEMUA absensi diambil dalam 1 query, lalu dikelompokkan di memori (anti N+1)
        $allStudentIds = $activeStudentsByClass->flatten()->pluck('id')->toArray();
        $attendancesByStudent = Attendance::whereIn('student_id', $allStudentIds)
            ->when($selectedYearId, fn($q) => $q->where('academic_year_id', $selectedYearId));
        
        $attendancesByStudent = $attendancesByStudent->get()
            ->groupBy('student_id');

        $classHistories = $classes->map(function ($cls) use ($selectedYearId, $activeYear, $todayAttendances, $activeStudentsByClass, $attendancesByStudent) {
            $classStudents = $activeStudentsByClass->get($cls->id, collect());
            $studentIds = $classStudents->pluck('id');
            $totalStudents = (int) ($cls->total_students ?? $classStudents->count());

            // Hitung presensi hari ini
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

            // Flag presensi hari ini: hanya aktif jika ada siswa yang tercatat hadir
            $hasTodayAttendance = ($todayRecorded > 0 && $todayHadir > 0);
            $persentaseToday = ($hasTodayAttendance && $totalStudents > 0)
                ? round(($todayHadir / $totalStudents) * 100)
                : 0;

            // Ambil absensi kelas ini dari data yang sudah di-fetch (tanpa query tambahan)
            $classAtts = collect();
            foreach ($studentIds as $sid) {
                $classAtts = $classAtts->merge($attendancesByStudent->get($sid, collect()));
            }

            $totalHari = $classAtts->pluck('date')->unique()->count();
            $countHadir = $classAtts->where('status', 'Hadir')->count();
            $countSakit = $classAtts->where('status', 'Sakit')->count();
            $countIzin = $classAtts->where('status', 'Izin')->count();
            $countAlfa = $classAtts->where('status', 'Alfa')->count();

            $tahunAjaranStr = $cls->academicYear
                ? ($cls->academicYear->name . ' – ' . $cls->academicYear->semester)
                : ($activeYear ? ($activeYear->name . ' – ' . $activeYear->semester) : '2026/2027 – Ganjil');

            return (object) [
                'id' => $cls->id,
                'name' => $cls->name ?? '-',
                'grade' => $cls->grade ?? $cls->level ?? '-',
                'tahun_ajaran' => $tahunAjaranStr,
                'teacher' => $cls->teacher ? $cls->teacher->name : 'Belum ditentukan',
                'teacher_obj' => $cls->teacher,
                'total_students' => $totalStudents,
                'total_hari' => $totalHari,
                'hadir' => $countHadir,
                'sakit' => $countSakit,
                'izin' => $countIzin,
                'alfa' => $countAlfa,
                'today_hadir' => $todayHadir,
                'today_recorded' => $todayRecorded,
                'has_today_attendance' => $hasTodayAttendance,
                'persentase' => $persentaseToday,
            ];
        });

        // Data per siswa untuk tabel histori kehadiran
        //
        // PERBAIKAN (2026-10-03): nilai class_filter dinormalisasi lebih dulu.
        // Option "Semua Kelas" bernilai kosong, tapi bila URL pernah membawa
        // label ("Semua Kelas") atau nilai sentinel lain, query lama
        // `whereHas('schoolClass', name = $classFilter)` tidak akan cocok dengan
        // kelas mana pun sehingga tabel selalu kosong.
        $classFilter = trim((string) $request->input('class_filter', ''));
        $isAllClasses = ($classFilter === '')
            || in_array(mb_strtolower($classFilter), ['semua kelas', 'semua', 'all', '0', '-'], true);

        $studentsQuery = Student::with('schoolClass')
            ->where('status', 'Aktif')
            ->select(['id', 'school_class_id', 'name', 'nisn']);

        if ($selectedYearId && $request->has('academic_year_id')) {
            $studentsQuery->whereHas('schoolClass', function ($q) use ($selectedYearId) {
                $q->where(function ($q2) use ($selectedYearId) {
                    $q2->where('academic_year_id', $selectedYearId)->orWhereNull('academic_year_id');
                });
            });
        }

        // Hanya filter kelas bila benar-benar memilih kelas tertentu.
        if (! $isAllClasses) {
            $studentsQuery->whereHas('schoolClass', function ($q) use ($classFilter) {
                $q->where('name', $classFilter);
            });
        }

        // Pencarian nama / NISN berlaku di SEMUA kelas, termasuk saat "Semua Kelas".
        if ($search !== '') {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $studentsQuery->orderBy('name', 'asc');

        // Paginasi server 100 siswa per halaman (sebelumnya 50) dengan seluruh query
        // string ikut terbawa, supaya pencarian & filter kelas tidak hilang.
        $studentPaginator = $studentsQuery->paginate(100)->withQueryString();
        $activeStudentsList = $studentPaginator->getCollection();

        $studentHistories = collect();

        /*
         * PERIODE daftar = HARI INI saja. Angka di kolom HADIR/TERLAMBAT/
         * SAKIT/IZIN/ALFA/BELUM adalah status HARI INI, tidak akumulasi
         * sejumlah hari. Ini IDENTIK dengan mode "Harian" di halaman
         * Riwayat Presensi (tanggal = hari ini), jadi angkanya selalu sinkron.
         */
        [$periodeMulai, $periodeAkhir, $periodeLabel] = Attendance::activeYearPeriodToday();

        // Hari belajar yang sudah lewat (sebelum hari ini) & hari ini.
        $hariSekolahLalu = Attendance::schoolDaysCount($periodeMulai, $periodeAkhir, true);
        $hariSekolahDepan = max(0, Attendance::schoolDaysCount($periodeMulai, $periodeAkhir, false) - $hariSekolahLalu);

        foreach ($activeStudentsList as $student) {
            $studentAtts = $attendancesByStudent->get($student->id, collect());

            // SATU sumber hitung (Attendance::summarizeStudent) untuk daftar ini
            // dan kartu ringkasan Riwayat Presensi.
            $ringkasan = Attendance::summarizeStudent($studentAtts, $hariSekolahLalu, $hariSekolahDepan);

            $studentHistories->push((object) [
                'id' => $student->id,
                'nisn' => $student->nisn ?: '-',
                'name' => $student->name ?? '-',
                'class_name' => $student->schoolClass?->name ?? '-',
                'hadir' => $ringkasan['hadir'],
                'terlambat' => $ringkasan['terlambat'],
                'sakit' => $ringkasan['sakit'],
                'izin' => $ringkasan['izin'],
                'alfa' => $ringkasan['alfa'],
                'belum' => $ringkasan['belum'],
            ]);
        }

        $studentPaginator->setCollection($studentHistories);
        $studentHistories = $studentPaginator;

        // Metrik Ringkasan Atas
        $totalKelas = $classHistories->count();
        $maxHari = $classHistories->max('total_hari') ?? 0;
        $totalSiswaSemua = $classHistories->sum('total_students');
        $classesWithAtt = $classHistories->filter(fn($c) => $c->has_today_attendance);
        $avgPersentase = $classesWithAtt->count() > 0 ? round($classesWithAtt->avg('persentase')) : 0;

        $props = [
            'classHistories' => $classHistories,
            'studentHistories' => $studentHistories,
            'periodeLabel' => $periodeLabel,
            'academicYears' => $academicYears,
            'selectedYearId' => $selectedYearId,
            'activeYear' => $activeYear,
            'search' => $search,
            'totalKelas' => $totalKelas,
            'maxHari' => $maxHari,
            'totalSiswaSemua' => $totalSiswaSemua,
            'avgPersentase' => $avgPersentase,
        ];

        // REST API JSON support
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($props);
        }

        return view('admin.attendances.kehadiran', $props);
    }

    /**
     * Riwayat presensi spesifik per siswa (dari menu Kehadiran).
     * Tabel ditampilkan secara horizontal (sama seperti rekap per kelas):
     * - Harian: No|NISN|NAMA|KELAS|JK|JAM MASUK|KETERLAMBATAN|STATUS|CATATAN
     * - Mingguan: No|NISN|NAMA|KELAS|JK|SEN..MIN|H|T|S|I|A|%
     * - Bulanan: No|NISN|NAMA|KELAS|JK|1..31|H|T|S|I|A|%
     * - Semester/Tahunan: No|NISN|NAMA|KELAS|JK|BULAN|H|T|S|I|A|%
     */
    public function studentHistory(Request $request, Student $student): View
    {
        Carbon::setLocale('id');
        $student->load('schoolClass');

        $period = (string) $request->input('period', 'harian');
        if (! in_array($period, ['harian', 'mingguan', 'bulanan', 'semua'], true)) {
            $period = 'semua';
        }

        // Untuk tab "Per Semester/Tahun", kullan dropdown:
        // Semester Ganjil, Semester Genap, Setahun.
        $periodSubType = (string) $request->input('period_subtype', 'semua');
        if (! in_array($periodSubType, ['ganjil', 'genap', 'semua', 'tahunan'], true)) {
            $periodSubType = 'semua';
        }

        $anchor = Carbon::parse($request->input('tanggal', Carbon::now('Asia/Jakarta')->toDateString()));

        // Determine date range based on period type and subtype
        if ($period === 'harian') {
            $startDate = $anchor->copy()->startOfDay();
            $endDate = $anchor->copy()->endOfDay();
            $periodLabel = $anchor->translatedFormat('l, d F Y');
        } elseif ($period === 'bulanan') {
            $startDate = $anchor->copy()->startOfMonth();
            $endDate = $anchor->copy()->endOfMonth();
            $periodLabel = $anchor->translatedFormat('F Y');
        } elseif ($period === 'mingguan') {
            $startDate = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $endDate = $anchor->copy()->endOfWeek(Carbon::SUNDAY);
            $periodLabel = $startDate->translatedFormat('d M')
                . ' - ' . $endDate->translatedFormat('d M Y');
        } else {
            // Semester/Tahunan: gunakan dropdown period_subtype
            $activeYear = AcademicYear::getActive();
            if ($periodSubType === 'ganjil') {
                // Semester Ganjil = Jul-Des (tahun awal TA)
                $taStart = $activeYear && $activeYear->start_date ? Carbon::parse($activeYear->start_date) : $anchor->copy()->addMonths(6);
                $startDate = $taStart->copy()->startOfMonth();
                $endDate = $taStart->copy()->addMonths(5)->endOfMonth();
                $periodLabel = 'Semester Ganjil ' . ($activeYear?->name ?? $startDate->format('Y'));
            } elseif ($periodSubType === 'genap') {
                // Semester Genap = Jan-Jun (tahun akhir TA)
                $taStart = $activeYear && $activeYear->start_date ? Carbon::parse($activeYear->start_date) : $anchor->copy()->addMonths(6);
                $startDate = $taStart->copy()->addMonths(6)->startOfMonth();
                $endDate = $activeYear && $activeYear->end_date ? Carbon::parse($activeYear->end_date) : $taStart->copy()->addMonths(11)->endOfMonth();
                $periodLabel = 'Semester Genap ' . ($activeYear?->name ?? $startDate->format('Y'));
            } elseif ($periodSubType === 'tahunan') {
                // Setahun = Jul-Jun
                $taStart = $activeYear && $activeYear->start_date ? Carbon::parse($activeYear->start_date) : $anchor->copy()->addMonths(6);
                $startDate = $taStart->copy()->startOfMonth();
                $endDate = $activeYear && $activeYear->end_date ? Carbon::parse($activeYear->end_date) : $taStart->copy()->addMonths(11)->endOfMonth();
                $periodLabel = 'Tahun Ajaran ' . ($activeYear?->name ?? $startDate->format('Y'));
            } else {
                // Mode "Semua": periode yang sama dengan daftar Catatan Kehadiran
                // (tahun berjalan) supaya angkanya bisa langsung dibandingkan.
                [$startDate, $endDate, $periodLabel] = Attendance::activeYearPeriod();
            }
        }

        // Keamanan: pastikan rentang tidak terbalik.
        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();

        // Data HANYA dari periode terpilih (bukan seluruh riwayat).
        $attendances = \App\Models\Attendance::where('student_id', $student->id)
            ->whereBetween('date', [$startDateStr, $endDateStr])
            ->orderBy('date', 'desc')
            ->get();

        $isHoliday = $period === 'harian'
            ? ($anchor->isWeekend() || \App\Models\Holiday::isHoliday($anchor->toDateString()))
            : false;

        // Build data horizontal seperti getRecapData
        $lateLimitTime = Setting::getLateLimitTime();

        // Untuk semester/tahunan, build kolom bulan
        $months = [];
        $dateColumns = [];
        $daysInMonth = null;
        $type = $period;

        if ($period === 'mingguan') {
            // Build date columns untuk mingguan
            $cur = Carbon::parse($startDateStr)->copy();
            $endObj = Carbon::parse($endDateStr);
            while ($cur->lte($endObj)) {
                $dateColumns[] = [
                    'date' => $cur->toDateString(),
                    'carbon' => $cur->copy(),
                    'label' => $cur->translatedFormat('D, d/m'),
                    'is_holiday' => $cur->isWeekend() || Holiday::isHoliday($cur->toDateString()),
                ];
                $cur->addDay();
            }
        } elseif ($period === 'bulanan') {
            $daysInMonth = (int) $anchor->daysInMonth;
            // Build date columns untuk bulanan
            $cur = Carbon::parse($startDateStr)->copy();
            $endObj = Carbon::parse($endDateStr);
            while ($cur->lte($endObj)) {
                $dateColumns[] = [
                    'date' => $cur->toDateString(),
                    'carbon' => $cur->copy(),
                    'label' => $cur->format('d'),
                    'is_holiday' => $cur->isWeekend() || Holiday::isHoliday($cur->toDateString()),
                ];
                $cur->addDay();
            }
            $type = 'bulanan';
        } elseif ($periodSubType !== 'semua') {
            // Semester/Tahunan: gunakan kolom bulan
            $months = $this->buildMonthColumns($startDateStr, $endDateStr);
            $type = $periodSubType === 'tahunan' ? 'tahunan' : 'semester';
        } else {
            // Mode "Semua" - gunakan periode tahun berjalan
            // Tidak ada kolom khusus, tampilkan sebagai bulanan sederhana
            $type = 'semua';
        }

        // Hitung stats menggunakan summarizeStudent, lalu ubah satuan baris
        // menggunakan satu helper Attendance (satu sumber ke Presensi & PDF).
        $hariSekolahLalu = Attendance::schoolDaysCount($startDate, $endDate, true);
        $hariSekolahDepan = max(0, Attendance::schoolDaysCount($startDate, $endDate, false) - $hariSekolahLalu);
        $totalHariBelajar = $hariSekolahLalu + $hariSekolahDepan;
        $stats = Attendance::summarizeStudent($attendances, $hariSekolahLalu, $hariSekolahDepan);

        $h = $stats['hadir'] + $stats['terlambat'];
        $t = $stats['terlambat'];
        $i = $stats['izin'];
        $a = $stats['alfa'];
        $percentage = $totalHariBelajar > 0 ? round(($h / $totalHariBelajar) * 100) : 0;

        // Build days array untuk mingguan/bulanan
        $days = [];
        if ($period === 'mingguan' || $period === 'bulanan') {
            $attendancesByDate = $attendances->keyBy(fn($att) => $att->date->toDateString());
            foreach ($dateColumns as $col) {
                $dStr = $col['date'];
                $att = $attendancesByDate->get($dStr);
                $code = '-';

                if ($att) {
                    $status = $att->effective_status;
                    if ($status === 'Hadir') {
                        $code = 'H';
                    } elseif ($status === 'Terlambat') {
                        $code = 'T';
                    } elseif ($status === 'Sakit') {
                        $code = 'S';
                    } elseif ($status === 'Izin') {
                        $code = 'I';
                    } elseif ($status === 'Alfa') {
                        $code = 'A';
                    }
                } else {
                    if ($col['is_holiday']) {
                        $code = 'L';
                    }
                }

                $days[$dStr] = $code;
            }
        } else {
            $days = [];
        }

        // Build monthly array untuk semester/tahunan
        $monthly = [];
        if (in_array($type, ['semester', 'tahunan'], true)) {
            $monthly = $this->buildMonthlyData($startDateStr, $endDateStr, $student->id, $months);
        } else {
            $monthly = [];
        }

        // Baris harian: satu nilai dari helper Attendance, tidak ada duplikasi
        // logika di view.
        if ($period === 'harian') {
            // Ambil record siswa pada tanggal itu (terbaru). Record yang ada
            // menang: weekend/libur/fallback tidak boleh menimpanya.
            $harianAttendance = \App\Models\Attendance::where('student_id', $student->id)
                ->whereDate('date', $anchor->toDateString())
                ->latest('date')
                ->first();
        } else {
            $harianAttendance = $attendances->first();
        }
        $harianRow = 
            \App\Models\Attendance::mapStudentAttendanceToRow($harianAttendance, $anchor->toDateString(), $isHoliday, $lateLimitTime, $student);

        $isHoliday = false;

        $rows = [[
            'no' => 1,
            'student' => $student,
            'days' => $days,
            'monthly' => $monthly,
            'hadir' => $stats['hadir'],
            'terlambat' => $t,
            'sakit' => $stats['sakit'],
            'izin' => $i,
            'alfa' => $a,
            'percentage' => $percentage,
            'harian' => $harianRow,
        ]];

        // Persentase untuk tampilan
        $attendanceRate = $percentage;

        if (!isset($dateColumns) || !is_array($dateColumns)) {
            $dateColumns = [];
        }
        if (!isset($daysInMonth) || $daysInMonth === null) {
            if ($period === 'bulanan') {
                $daysInMonth = (int) $anchor->daysInMonth;
            } else {
                $daysInMonth = null;
            }
        }
        if (!isset($months) || !is_array($months)) {
            $months = [];
        }
        if (!isset($totalHariBelajar)) {
            $totalHariBelajar = $hariSekolahLalu + $hariSekolahDepan;
        }

        $activeYearResolved = $activeYear ?? AcademicYear::getActive();

        return view('admin.kehadiran.student-history', [
            'student' => $student,
            'attendances' => $attendances,
            'rows' => $rows,
            'stats' => $stats,
            'attendanceRate' => $attendanceRate,
            'period' => $period,
            'periodLabel' => $periodLabel,
            'anchor' => $anchor,
            'totalHariBelajar' => $totalHariBelajar,
            'type' => $type,
            'months' => $months,
            'dateColumns' => $dateColumns,
            'daysInMonth' => $daysInMonth,
            'periodSubType' => $periodSubType,
            'activeYear' => $activeYearResolved,
            'days' => $days,
            'monthly' => $monthly,
            'summary' => $stats,
        ]);
    }

    /**
     * Build kolom bulan untuk semester/tahunan.
     */
    protected function buildMonthColumns(string $startDate, string $endDate): array
    {
        $columns = [];
        $cursor = Carbon::parse($startDate)->startOfMonth();
        $last = Carbon::parse($endDate)->startOfMonth();

        while ($cursor->lte($last)) {
            $columns[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->translatedFormat('M'),
                'year' => (int) $cursor->format('Y'),
                'month' => (int) $cursor->format('m'),
            ];
            $cursor->addMonthNoOverflow();
        }

        return $columns;
    }

    /**
     * Build data bulanan untuk semester/tahunan.
     */
    protected function buildMonthlyData(string $startDate, string $endDate, int $studentId, array $months): array
    {
        $monthly = [];
        $attendances = \App\Models\Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        foreach ($months as $monthInfo) {
            $ym = $monthInfo['key'];
            $monthAttendances = $attendances->filter(fn($att) => substr($att->date->toDateString(), 0, 7) === $ym);

            $hadir = 0;
            foreach ($monthAttendances as $att) {
                if (in_array($att->effective_status, ['Hadir', 'Terlambat'], true)) {
                    $hadir++;
                }
            }

            $monthly[$ym] = $hadir;
        }

        return $monthly;
    }

    /**
     * Halaman Detail Catatan Kehadiran per Kelas - /admin/kehadiran/{schoolClass}
     */
    public function kehadiranDetail(Request $request, SchoolClass $schoolClass)
    {
        Carbon::setLocale('id');
        $schoolClass->load(['teacher', 'academicYear']);

        $academicYears = AcademicYear::orderByDesc('is_active')->get();
        $activeYear = AcademicYear::getActive() ?? $academicYears->first();
        $selectedYearId = $request->input('academic_year_id', $schoolClass->academic_year_id ?? $activeYear?->id);

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Ambil data siswa di kelas ini
        $students = Student::where('school_class_id', $schoolClass->id)
            ->where('status', 'Aktif')
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        // Total hari efektif kelas ini
        $totalHari = (int) Attendance::whereIn('student_id', $studentIds)
            ->when($selectedYearId, fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->distinct('date')
            ->count('date');

        // Hitung rekapan presensi per siswa (batch query, hindari N+1)
        $allAttendances = Attendance::whereIn('student_id', $students->pluck('id'))
            ->when($selectedYearId, fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->when($month, fn($q) => $q->whereMonth('date', $month))
            ->when($year, fn($q) => $q->whereYear('date', $year))
            ->get()
            ->groupBy('student_id');

        $studentRecaps = $students->map(function ($student) use ($allAttendances) {
            $atts = $allAttendances->get($student->id, collect());

            $hadir = $atts->where('status', 'Hadir')->where('time_remark', '!=', 'Terlambat')->count();
            $terlambat = $atts->where('status', 'Hadir')->where('time_remark', 'Terlambat')->count();
            $sakit = $atts->where('status', 'Sakit')->count();
            $izin = $atts->where('status', 'Izin')->count();
            $alfa = $atts->where('status', 'Alfa')->count();

            $totalAtt = $atts->count();
            $persentase = $totalAtt > 0
                ? round((($hadir + $terlambat) / $totalAtt) * 100)
                : 0;

            return (object) [
                'id' => $student->id,
                'nisn' => $student->nisn ?: '-',
                'name' => $student->name,
                'gender' => $student->gender ?? 'L',
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'sakit' => $sakit,
                'izin' => $izin,
                'alfa' => $alfa,
                'total_hadir' => $hadir + $terlambat,
                'total_absen' => $totalAtt,
                'persentase' => $persentase,
            ];
        });

        $tahunAjaranStr = $schoolClass->academicYear
            ? ($schoolClass->academicYear->name . ' – ' . $schoolClass->academicYear->semester)
            : ($activeYear ? ($activeYear->name . ' – ' . $activeYear->semester) : '2026/2027 – Ganjil');

        $props = [
            'schoolClass' => $schoolClass,
            'studentRecaps' => $studentRecaps,
            'tahunAjaranStr' => $tahunAjaranStr,
            'totalHari' => $totalHari,
            'academicYears' => $academicYears,
            'selectedYearId' => $selectedYearId,
            'month' => $month,
            'year' => $year,
        ];

        return view('admin.attendances.kehadiran_detail', $props);
    }

    public function override(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'date' => 'required|date',
            'status' => ['required', 'in:' . implode(',', Attendance::VALID_STATUSES)],
            'check_in' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
            'proof_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        /*
         * Validasi tambahan (server-side, bukan cuma di form):
         * - tanggal tidak boleh di masa depan (data hanya bisa ditulis
         *   untuk hari ini atau hari-hari sebelumnya);
         * - hari libur tidak boleh diisi manual (sistem punya aturan hari
         *   libur sendiri);
         * - bila class_id dikirim, siswa WAJIB benar-benar anggota kelas itu
         *   (mencegah simpan presensi siswa dari kelas lain).
         */
        $now = Carbon::now('Asia/Jakarta');
        $tanggal = (string) $request->input('date');

        if (Carbon::parse($tanggal)->startOfDay()->gt($now->copy()->startOfDay())) {
            return back()->with('error', __('Tanggal presensi tidak boleh di masa depan.'));
        }

        if (Holiday::isHoliday($tanggal)) {
            return back()->with('error', __('Presensi pada hari libur tidak dapat diisi.'));
        }

        if ($request->filled('class_id')) {
            $studentClassId = Student::where('id', $request->input('student_id'))->value('school_class_id');

            if ((int) $studentClassId !== (int) $request->input('class_id')) {
                return back()->with('error', __('Siswa ini bukan anggota kelas yang dipilih.'));
            }
        }

        // Validasi tambahan: pastikan file upload aman
        if ($request->hasFile('proof_document')) {
            $file = $request->file('proof_document');
            // Validasi MIME type server-side
            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
            $mime = $file->getMimeType();
            if (!in_array($mime, $allowedMimes, true)) {
                return back()->with('error', __('Format file tidak diizinkan. Gunakan PDF, JPG, atau PNG.'));
            }
            // Validasi ekstensi
            $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
            $ext = strtolower($file->extension());
            if (!in_array($ext, $allowedExts, true)) {
                return back()->with('error', __('Ekstensi file tidak diizinkan.'));
            }
        }

        $activeYear = AcademicYear::getActive();

        $requestedStatus = $request->status;
        $batasTerlambat = Setting::getLateLimitTime();

        // Hadir & Terlambat sama-sama berarti SISWA HADIR (terlambat = hadir
        // yang datang telat). Keduanya disimpan sebagai status 'Hadir' +
        // penanda terlambat, supaya semua query lama yang menghitung
        // `status = 'Hadir'` (Dashboard, Rekap, Kehadiran) tetap benar.
        $isAttendance = in_array($requestedStatus, [Attendance::STATUS_HADIR, Attendance::STATUS_TERLAMBAT], true);

        $status = $requestedStatus;
        $timeRemark = null;
        $isLate = false;
        $lateMinutes = 0;

        $attendance = Attendance::firstOrNew([
            'student_id' => $request->student_id,
            'date' => $request->date,
        ]);

        if (!$attendance->exists) {
            $attendance->academic_year_id = $activeYear?->id;
        }

        /*
         * JAM MASUK (Prioritas 2):
         * - Hadir / Terlambat => jam masuk WAJIB ada.
         *   * kalau admin mengirim jam (modal override) => pakai jam itu;
         *   * kalau tidak dikirim (dropdown cepat) dan belum ada jam =>
         *     isi otomatis dengan waktu sekarang Asia/Jakarta;
         *   * kalau sudah ada jam sebelumnya => JAGA (tidak dihapus), supaya
         *     mengubah Hadir <-> Terlambat tidak menghilangkan jam masuk.
         * - Sakit / Izin / Alfa => jam masuk dikosongkan (wajar, bukan hadir).
         */
        if ($isAttendance) {
            if ($request->filled('check_in')) {
                $jamMasuk = substr(trim((string) $request->check_in), 0, 8);
                if (strlen($jamMasuk) === 5) {
                    $jamMasuk .= ':00';
                }
            } elseif (!empty($attendance->check_in)) {
                $jamMasuk = $attendance->check_in;
            } else {
                $jamMasuk = Carbon::now('Asia/Jakarta')->format('H:i:s');
            }

            $attendance->check_in = $jamMasuk;

            // Satu-satunya penentu status terlambat (satu sumber kebenaran).
            $evaluasi = Attendance::evaluateCheckInTime($jamMasuk, $batasTerlambat);

            if ($requestedStatus === Attendance::STATUS_TERLAMBAT) {
                // Pilihan admin = koreksi manual, DIDAHULUKAN walaupun jamnya
                // kebetulan masih di bawah batas terlambat. late_minutes tetap
                // dihitung dari jam yang benar-benar tersimpan.
                $timeRemark = Attendance::REMARK_TERLAMBAT;
                $isLate = true;
                $lateMinutes = (int) $evaluasi['late_minutes'];
            } else {
                // "Hadir" dipilih eksplisit: status mengikuti jam yang tersimpan
                // bila ada jam, supaya tidak ada data yang bertentangan.
                $timeRemark = $evaluasi['time_remark'];
                $isLate = (bool) $evaluasi['is_late'];
                $lateMinutes = (int) $evaluasi['late_minutes'];
            }

            $status = Attendance::STATUS_HADIR;
            $attendance->is_late = $isLate;
            $attendance->late_minutes = $lateMinutes;
        } else {
            // Tidak hadir: jam masuk dikosongkan.
            $status = $requestedStatus;
            $timeRemark = null;
            $attendance->check_in = null;
            $attendance->is_late = false;
            $attendance->late_minutes = 0;
        }

        $attendance->status = $status;
        $attendance->time_remark = $timeRemark;
        $attendance->notes = $request->notes;

        if ($request->hasFile('proof_document')) {
            if ($attendance->proof_document && Storage::disk('public')->exists($attendance->proof_document)) {
                Storage::disk('public')->delete($attendance->proof_document);
            }
            $attendance->proof_document = $request->file('proof_document')->store('attendance_proofs', 'public');
        }

        $attendance->save();

        return back()->with('success', __('Status presensi siswa berhasil diperbarui!'));
    }

}

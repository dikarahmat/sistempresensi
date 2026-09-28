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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Halaman Utama Absensi Harian (Support Inertia.js props, Blade view, dan REST API)
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
        $isSunday = $selectedDateObj->isSunday();
        $isHoliday = Holiday::isHoliday($tanggal) || $isSunday;
        $holidayDescription = Holiday::getHolidayDescription($tanggal) ?? ($isSunday ? 'Hari Minggu (Akhir Pekan)' : null);

        $jamMasuk = Setting::getCheckInTime();
        $jamPulang = Setting::getCheckOutTime();
        $batasTerlambat = Setting::getLateLimitTime();

        $formattedJamMasuk = strlen($jamMasuk) === 5 ? $jamMasuk . ':00' : $jamMasuk;
        $formattedJamPulang = strlen($jamPulang) === 5 ? $jamPulang . ':00' : $jamPulang;

        // Data profil sekolah & pengaturan operasional
        $schoolInfo = [
            'nama_sekolah' => Setting::getSchoolName(),
            'app_title' => Setting::getAppTitle(),
            'logo' => Setting::getLogo(),
            'jam_masuk' => $formattedJamMasuk,
            'jam_pulang' => $formattedJamPulang,
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

            $processedStudents = $students->map(function ($student) use ($tanggal, $now, $isHoliday, $batasTerlambat) {
                $att = $student->attendances->first();
                $status = 'Belum Hadir';
                $timeRemark = null;
                $lateMinutes = 0;
                $checkIn = null;
                $checkOut = null;
                $notes = null;
                $proofDocument = null;

                if ($att) {
                    $status = $att->status;
                    $timeRemark = $att->time_remark;
                    $checkIn = $att->check_in;
                    $checkOut = $att->check_out;
                    $notes = $att->notes;
                    $proofDocument = $att->proof_document;

                    if ($status === 'Hadir' && $timeRemark === 'Terlambat' && $checkIn) {
                        $checkInCarbon = Carbon::parse($checkIn);
                        $lateLimitCarbon = Carbon::parse($batasTerlambat);
                        $diff = $checkInCarbon->diffInMinutes($lateLimitCarbon, false);
                        $lateMinutes = $diff < 0 ? abs($diff) : 0;
                    }
                } else {
                    if ($isHoliday) {
                        $status = 'Libur';
                    } elseif ($tanggal < $now->toDateString()) {
                        $status = 'Alfa';
                    } elseif ($tanggal === $now->toDateString()) {
                        $status = ($now->format('H:i') > substr($batasTerlambat, 0, 5)) ? 'Alfa' : 'Belum Hadir';
                    } else {
                        $status = '-';
                    }
                }

                $student->current_status = $status;
                $student->current_time_remark = $timeRemark;
                $student->late_minutes = $lateMinutes;
                $student->check_in_time = $checkIn;
                $student->check_out_time = $checkOut;
                $student->notes = $notes;
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

        // Props terstruktur untuk Inertia.js maupun Blade view
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

        // 1. Dukungan Inertia.js jika package terinstall
        if (class_exists(\Inertia\Inertia::class)) {
            return \Inertia\Inertia::render('Admin/Absensi/Index', $props);
        }

        // 2. Dukungan JSON jika request via REST / Vue API
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($props);
        }

        // 3. Render Blade View Standar
        $classSummaries = $kelasList->map(function ($item) {
            return (object) [
                'id' => $item['id'],
                'name' => $item['nama_kelas'],
                'grade' => $item['tingkat'],
                'teacher' => (object) ['name' => $item['wali_kelas']],
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
    public function showClass(Request $request, SchoolClass|int $schoolClass)
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
        $isSunday = $selectedDateObj->isSunday();
        $isHoliday = Holiday::isHoliday($tanggal) || $isSunday;
        $holidayDescription = Holiday::getHolidayDescription($tanggal) ?? ($isSunday ? 'Hari Minggu (Akhir Pekan)' : null);

        $jamMasuk = Setting::getCheckInTime();
        $jamPulang = Setting::getCheckOutTime();
        $batasTerlambat = Setting::getLateLimitTime();

        $formattedCheckIn = strlen($jamMasuk) === 5 ? $jamMasuk . ':00' : $jamMasuk;
        $formattedCheckOut = strlen($jamPulang) === 5 ? $jamPulang . ':00' : $jamPulang;

        // Ambil seluruh siswa aktif di kelas ini
        $students = Student::where('school_class_id', $schoolClass->id)
            ->where('status', 'Aktif')
            ->with(['attendances' => fn($q) => $q->where('date', $tanggal)])
            ->orderBy('name')
            ->get();

        $countHadir = 0;
        $countTerlambat = 0;
        $countSakit = 0;
        $countIzin = 0;
        $countAlfa = 0;

        $studentsList = $students->map(function ($student) use (
            $tanggal, $now, $isHoliday, $batasTerlambat,
            &$countHadir, &$countTerlambat, &$countSakit, &$countIzin, &$countAlfa
        ) {
            $att = $student->attendances->first();
            $status = 'Belum Hadir';
            $timeRemark = null;
            $lateMinutes = 0;
            $checkIn = null;
            $checkOut = null;
            $notes = null;
            $proofDocument = null;

            if ($att) {
                $status = $att->status;
                $timeRemark = $att->time_remark;
                $checkIn = $att->check_in;
                $checkOut = $att->check_out;
                $notes = $att->notes;
                $proofDocument = $att->proof_document;

                if ($status === 'Hadir') {
                    if ($timeRemark === 'Terlambat') {
                        $countTerlambat++;
                        $countHadir++;
                        if ($checkIn) {
                            $checkInCarbon = Carbon::parse($checkIn);
                            $lateLimitCarbon = Carbon::parse($batasTerlambat);
                            $diff = $checkInCarbon->diffInMinutes($lateLimitCarbon, false);
                            $lateMinutes = $diff < 0 ? abs($diff) : 0;
                        }
                    } else {
                        $countHadir++;
                    }
                } elseif ($status === 'Sakit') {
                    $countSakit++;
                } elseif ($status === 'Izin') {
                    $countIzin++;
                } elseif ($status === 'Alfa') {
                    $countAlfa++;
                }
            } else {
                if ($isHoliday) {
                    $status = 'Libur';
                } elseif ($tanggal < $now->toDateString()) {
                    $status = 'Alfa';
                    $countAlfa++;
                } elseif ($tanggal === $now->toDateString()) {
                    if ($now->format('H:i') > substr($batasTerlambat, 0, 5)) {
                        $status = 'Alfa';
                        $countAlfa++;
                    } else {
                        $status = 'Belum Hadir';
                    }
                } else {
                    $status = '-';
                }
            }

            return (object) [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis ?? '-',
                'nisn' => $student->nisn ?? '-',
                'gender' => $student->gender ?? 'L',
                'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                'jam_masuk' => $checkIn ? (substr($checkIn, 0, 5) . ' WIB') : '—',
                'jam_pulang' => $checkOut ? (substr($checkOut, 0, 5) . ' WIB') : '—',
                'raw_check_in' => $checkIn,
                'raw_check_out' => $checkOut,
                'current_status' => $status,
                'current_time_remark' => $timeRemark,
                'late_minutes' => $lateMinutes,
                'notes' => $notes,
                'proof_document' => $proofDocument,
                'attendance_id' => $att?->id,
            ];
        });

        $totalSiswa = $studentsList->count();
        $countSudahAbsen = $countHadir + $countSakit + $countIzin + $countAlfa;
        $countBelumAbsen = max(0, $totalSiswa - $countSudahAbsen);
        $countHadirTepat = max(0, $countHadir - $countTerlambat);

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

        if ($search !== '') {
            $classesQuery->where('name', 'like', "%{$search}%");
        }

        $classes = $classesQuery->orderBy('grade')->orderBy('name')->get();

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Ambil data absensi hari ini sekaligus secara efisien (in-memory lookup O(1))
        $todayAttendances = Attendance::where('date', $today)
            ->select(['id', 'student_id', 'status'])
            ->get()
            ->keyBy('student_id');

        // Ambil data siswa aktif yang dipetakan per kelas
        $activeStudentsByClass = Student::where('status', 'Aktif')
            ->select(['id', 'school_class_id', 'name', 'nis'])
            ->get()
            ->groupBy('school_class_id');

        // Hitung histori kumulatif & presensi hari ini per rombel
        // SEMUA absensi diambil dalam 1 query, lalu dikelompokkan di memori (anti N+1)
        // OPTIMASI: Batasi query dengan filter tahun ajaran + chunk untuk memory efficiency
        $allStudentIds = $activeStudentsByClass->flatten()->pluck('id')->toArray();
        $attendancesByStudent = collect();
        
        // Chunk query untuk menghindari memory overflow pada dataset besar
        $query = Attendance::whereIn('student_id', $allStudentIds);
        if ($selectedYearId) {
            $query->where(function ($q) use ($selectedYearId) {
                $q->where('academic_year_id', $selectedYearId)->orWhereNull('academic_year_id');
            });
        }
        
        $query->chunk(1000, function ($attendances) use (&$attendancesByStudent) {
            foreach ($attendances as $att) {
                $attendancesByStudent->push($att);
            }
        });
        
        $attendancesByStudent = $attendancesByStudent->groupBy('student_id');

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
        // Ambil SEMUA siswa aktif (tidak tergantung filter kelas pada $classes)
        // agar "Semua Kelas" selalu menampilkan data
        $allActiveStudents = Student::with('schoolClass')
            ->where('status', 'Aktif')
            ->select(['id', 'school_class_id', 'name', 'nis']);

        if ($selectedYearId && $request->has('academic_year_id')) {
            $allActiveStudents->whereHas('schoolClass', function ($q) use ($selectedYearId) {
                $q->where(function ($q2) use ($selectedYearId) {
                    $q2->where('academic_year_id', $selectedYearId)->orWhereNull('academic_year_id');
                });
            });
        }

        $allActiveStudents = $allActiveStudents->get();

        $studentHistories = collect();
        foreach ($allActiveStudents as $student) {
            $studentAtts = $attendancesByStudent->get($student->id, collect());
            $studentHistories->push((object) [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name ?? '-',
                'class_name' => $student->schoolClass?->name ?? '-',
                'hadir' => $studentAtts->where('status', 'Hadir')->count(),
                'terlambat' => $studentAtts->where('status', 'Terlambat')->count(),
                'sakit' => $studentAtts->where('status', 'Sakit')->count(),
                'izin' => $studentAtts->where('status', 'Izin')->count(),
                'alfa' => $studentAtts->where('status', 'Alfa')->count(),
            ]);
        }

        // Filter search dan kelas untuk tabel siswa
        $classFilter = $request->input('class_filter', '');
        if ($classFilter !== '') {
            $studentHistories = $studentHistories->filter(fn($s) => $s->class_name === $classFilter);
        }
        if ($search !== '') {
            $studentHistories = $studentHistories->filter(function ($s) use ($search) {
                return str_contains(strtolower($s->name), strtolower($search)) 
                    || str_contains(strtolower($s->nis), strtolower($search));
            });
        }

        // Metrik Ringkasan Atas
        $totalKelas = $classHistories->count();
        $maxHari = $classHistories->max('total_hari') ?? 0;
        $totalSiswaSemua = $classHistories->sum('total_students');
        $classesWithAtt = $classHistories->filter(fn($c) => $c->has_today_attendance);
        $avgPersentase = $classesWithAtt->count() > 0 ? round($classesWithAtt->avg('persentase')) : 0;

        $props = [
            'classHistories' => $classHistories,
            'studentHistories' => $studentHistories,
            'academicYears' => $academicYears,
            'selectedYearId' => $selectedYearId,
            'activeYear' => $activeYear,
            'search' => $search,
            'totalKelas' => $totalKelas,
            'maxHari' => $maxHari,
            'totalSiswaSemua' => $totalSiswaSemua,
            'avgPersentase' => $avgPersentase,
        ];

        // Inertia.js support
        if (class_exists(\Inertia\Inertia::class)) {
            return \Inertia\Inertia::render('Admin/Kehadiran/Index', $props);
        }

        // REST API JSON support
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($props);
        }

        return view('admin.attendances.kehadiran', $props);
    }

    /**
     * Riwayat presensi spesifik per siswa (dari menu Kehadiran).
     */
    public function studentHistory(Request $request, Student $student): View
    {
        Carbon::setLocale('id');
        $student->load('schoolClass');

        $startDate = $request->input('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $attendances = \App\Models\Attendance::where('student_id', $student->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $stats = [
            'hadir' => $attendances->where('status', 'Hadir')->count(),
            // Keterlambatan disimpan pada kolom time_remark (status tetap 'Hadir')
            'terlambat' => $attendances->filter(fn ($att) => $att->status === 'Hadir' && $att->time_remark === 'Terlambat')->count(),
            'sakit' => $attendances->where('status', 'Sakit')->count(),
            'izin' => $attendances->where('status', 'Izin')->count(),
            'alfa' => $attendances->where('status', 'Alfa')->count(),
        ];

        $totalDays = $attendances->count();
        $attendanceRate = $totalDays > 0 ? round(($stats['hadir'] / $totalDays) * 100, 1) : 0;

        $period = $request->input('period', 'mingguan');

        return view('admin.kehadiran.student-history', compact(
            'student', 'attendances', 'stats', 'totalDays', 'attendanceRate',
            'startDate', 'endDate', 'period'
        ));
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
                'nis' => $student->nis ?? '-',
                'nisn' => $student->nisn ?? '-',
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
            'date' => 'required|date',
            'status' => 'required|in:Hadir,Terlambat,Sakit,Izin,Alfa',
            'check_in' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
            'proof_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $activeYear = AcademicYear::getActive();

        $status = $request->status;
        $timeRemark = null;

        if ($status === 'Terlambat') {
            $status = 'Hadir';
            $timeRemark = 'Terlambat';
        } elseif ($status === 'Hadir') {
            $timeRemark = 'Tepat Waktu';
        }

        $attendance = Attendance::firstOrNew([
            'student_id' => $request->student_id,
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

        return back()->with('success', 'Status presensi siswa berhasil diperbarui!');
    }

}

<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KesiswaanAttendanceController extends Controller
{
    /**
     * Halaman Utama Presensi Harian Kesiswaan (Strict Read-Only).
     * 100% Identik secara struktur data dan variabel dengan Admin.
     */
    public function index(Request $request)
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

        $schoolInfo = [
            'nama_sekolah' => Setting::getSchoolName(),
            'app_title' => Setting::getAppTitle(),
            'logo' => Setting::getLogo(),
            'jam_masuk' => $formattedJamMasuk,
            'jam_pulang' => $formattedJamPulang,
            'batas_terlambat' => $batasTerlambat,
        ];

        // Seluruh kelas sekolah
        $classes = SchoolClass::with(['teacher'])
            ->withCount(['students as total_siswa' => fn($q) => $q->where('status', 'Aktif')])
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        $allTodayAttendances = Attendance::where('date', $tanggal)
            ->select(['id', 'student_id', 'status', 'time_remark'])
            ->get();

        $activeStudents = Student::where('status', 'Aktif')
            ->select(['id', 'school_class_id'])
            ->get();

        $studentsByClass = $activeStudents->groupBy('school_class_id');
        $attendancesByStudent = $allTodayAttendances->keyBy('student_id');

        $kelasList = $classes->map(function ($cls) use ($studentsByClass, $attendancesByStudent) {
            $classStudents = $studentsByClass->get($cls->id, collect());
            $totalSiswa = (int) ($cls->total_siswa ?? $classStudents->count());

            $hadir = 0;
            $terlambat = 0;
            $sakit = 0;
            $izin = 0;
            $alpha = 0;

            foreach ($classStudents as $student) {
                $att = $attendancesByStudent->get($student->id);
                if ($att) {
                    if ($att->status === 'Hadir') {
                        $hadir++;
                        if ($att->time_remark === 'Terlambat') {
                            $terlambat++;
                        }
                    } elseif ($att->status === 'Sakit') {
                        $sakit++;
                    } elseif ($att->status === 'Izin') {
                        $izin++;
                    } elseif ($att->status === 'Alfa' || $att->status === 'Alpha') {
                        $alpha++;
                    }
                }
            }

            $sudahAbsen = $hadir + $sakit + $izin + $alpha;
            $belum = max(0, $totalSiswa - $sudahAbsen);
            $persentase = $totalSiswa > 0
                ? round(($hadir / $totalSiswa) * 100)
                : 0;

            return [
                'id' => $cls->id,
                'nama_kelas' => $cls->name ?? '-',
                'tingkat' => $cls->grade ?? $cls->level ?? '-',
                'wali_kelas' => $cls->teacher ? $cls->teacher->name : 'Belum ditentukan',
                'total_siswa' => $totalSiswa,
                'sudah_absen' => $sudahAbsen,
                'belum' => $belum,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpha' => $alpha,
                'persentase' => $persentase,
            ];
        });

        // Hitung total ringkasan
        $totalSiswaSekolah = (int) $activeStudents->count();
        $totalSudahAbsen = (int) $kelasList->sum('sudah_absen');
        $totalBelumAbsen = max(0, $totalSiswaSekolah - $totalSudahAbsen);
        $totalHadir = (int) $kelasList->sum('hadir');
        $totalTerlambat = (int) $kelasList->sum('terlambat');
        $totalSakit = (int) $kelasList->sum('sakit');
        $totalIzin = (int) $kelasList->sum('izin');
        $totalAlpha = (int) $kelasList->sum('alpha');

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
            'hadir_count' => $totalHadir + $totalTerlambat,
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($props);
        }

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

        return view('kesiswaan.absensi.index', array_merge($props, [
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
     * Halaman Detail Absensi Kelas (Strict Read-Only).
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

        return view('kesiswaan.absensi.class', $props);
    }

    /**
     * Halaman Catatan Kehadiran (Histori Kumulatif per Rombel).
     */
    public function kehadiran(Request $request)
    {
        Carbon::setLocale('id');
        $academicYears = AcademicYear::orderByDesc('is_active')->orderByDesc('start_date')->get();
        $activeYear = AcademicYear::getActive() ?? $academicYears->first();
        $selectedYearId = $request->input('academic_year_id', $activeYear?->id);
        $search = trim($request->input('search', ''));

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

        $todayAttendances = Attendance::where('date', $today)
            ->select(['id', 'student_id', 'status'])
            ->get()
            ->keyBy('student_id');

        $activeStudentsByClass = Student::where('status', 'Aktif')
            ->select(['id', 'school_class_id'])
            ->get()
            ->groupBy('school_class_id');

        $classHistories = $classes->map(function ($cls) use ($selectedYearId, $activeYear, $todayAttendances, $activeStudentsByClass) {
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

            $hasTodayAttendance = ($todayRecorded > 0 && $todayHadir > 0);
            $persentaseToday = ($hasTodayAttendance && $totalStudents > 0)
                ? round(($todayHadir / $totalStudents) * 100)
                : 0;

            $attQuery = Attendance::whereIn('student_id', $studentIds);
            if ($selectedYearId) {
                $attQuery->where(function ($q) use ($selectedYearId) {
                    $q->where('academic_year_id', $selectedYearId)->orWhereNull('academic_year_id');
                });
            }

            $totalHari = (int) $attQuery->distinct('date')->count('date');

            $allAtts = $attQuery->get();
            $countHadir = $allAtts->where('status', 'Hadir')->count();
            $countSakit = $allAtts->where('status', 'Sakit')->count();
            $countIzin = $allAtts->where('status', 'Izin')->count();
            $countAlfa = $allAtts->where('status', 'Alfa')->count();

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

        $totalKelas = $classHistories->count();
        $maxHari = $classHistories->max('total_hari') ?? 0;
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
        ];

        return view('kesiswaan.attendances.kehadiran', $props);
    }

    /**
     * Halaman Detail Catatan Kehadiran per Kelas (Histori Bulanan).
     */
    public function kehadiranDetail(Request $request, SchoolClass|int $schoolClass)
    {
        Carbon::setLocale('id');

        if (!($schoolClass instanceof SchoolClass)) {
            $schoolClass = SchoolClass::findOrFail($schoolClass);
        }

        $schoolClass->load(['teacher', 'academicYear']);

        $academicYears = AcademicYear::orderByDesc('is_active')->get();
        $activeYear = AcademicYear::getActive() ?? $academicYears->first();
        $selectedYearId = $request->input('academic_year_id', $schoolClass->academic_year_id ?? $activeYear?->id);

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $students = Student::where('school_class_id', $schoolClass->id)
            ->where('status', 'Aktif')
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        $totalHari = (int) Attendance::whereIn('student_id', $studentIds)
            ->when($selectedYearId, fn($q) => $q->where('academic_year_id', $selectedYearId))
            ->distinct('date')
            ->count('date');

        $studentRecaps = $students->map(function ($student) use ($selectedYearId, $month, $year) {
            $atts = Attendance::where('student_id', $student->id)
                ->when($selectedYearId, fn($q) => $q->where('academic_year_id', $selectedYearId))
                ->when($month, fn($q) => $q->whereMonth('date', $month))
                ->when($year, fn($q) => $q->whereYear('date', $year))
                ->get();

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

        return view('kesiswaan.attendances.kehadiran_detail', $props);
    }
}

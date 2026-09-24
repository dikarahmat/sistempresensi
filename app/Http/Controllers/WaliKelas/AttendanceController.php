<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Dapatkan Guru dan Kelas yang diampu oleh Wali Kelas login.
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
     * Halaman Utama Presensi Harian Wali Kelas.
     * Menggunakan struktur data yang sinkron dengan Admin AttendanceController.
     * Scope data WAJIB terkunci hanya untuk siswa di kelas binaan guru tersebut.
     */
    public function index(Request $request): View
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $hariIni = $now->toDateString();
        $tanggal = $request->input('tanggal', $request->input('date', $hariIni));
        $classId = $request->input('class_id');

        [$teacher, $schoolClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass($request);

        // Jika request spesifik class_id dan tanpa tab, tampilkan detail kelas langsung
        if ($request->filled('class_id') && !$request->has('tab')) {
            $cls = $teacherClasses->firstWhere('id', $request->class_id);
            if ($cls) {
                return $this->showClass($request, $cls);
            } else {
                abort(403, 'Akses ditolak: Anda tidak memiliki akses ke kelas ini.');
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

        $teacherClassIds = $teacherClasses->pluck('id')->toArray();

        // 1. Ambil siswa aktif HANYA di kelas binaan guru
        $activeStudents = collect();
        if (!empty($teacherClassIds)) {
            $activeStudents = Student::whereIn('school_class_id', $teacherClassIds)
                ->where('status', 'Aktif')
                ->select(['id', 'school_class_id', 'name', 'nis', 'nisn', 'gender', 'photo'])
                ->get();
        }

        $studentIds = $activeStudents->pluck('id');

        // 2. Ambil absensi hari ini HANYA untuk siswa binaan guru
        $allTodayAttendances = collect();
        if ($studentIds->isNotEmpty()) {
            $allTodayAttendances = Attendance::where('date', $tanggal)
                ->whereIn('student_id', $studentIds)
                ->select(['id', 'student_id', 'status', 'time_remark', 'check_in', 'check_out', 'notes', 'proof_document'])
                ->get();
        }

        $studentsByClass = $activeStudents->groupBy('school_class_id');
        $attendancesByStudent = $allTodayAttendances->keyBy('student_id');

        // 3. Rekap per kelas binaan
        $kelasList = $teacherClasses->map(function ($cls) use ($studentsByClass, $attendancesByStudent) {
            $classStudents = $studentsByClass->get($cls->id, collect());
            $totalSiswa = $classStudents->count();

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

        // 4. Hitung ringkasan total khusus siswa binaan
        $totalSiswaSekolah = (int) $activeStudents->count();
        $totalSudahAbsen = (int) $kelasList->sum('sudah_absen');
        $totalBelumAbsen = max(0, $totalSiswaSekolah - $totalSudahAbsen);
        $totalHadir = (int) $kelasList->sum('hadir');
        $totalTerlambat = (int) $kelasList->sum('terlambat');
        $totalSakit = (int) $kelasList->sum('sakit');
        $totalIzin = (int) $kelasList->sum('izin');
        $totalAlpha = (int) $kelasList->sum('alpha');

        // 5. Query detail siswa terfilter jika status diisi
        $processedStudents = collect();
        if ($classId || $request->filled('status')) {
            $querySiswa = Student::where('status', 'Aktif')
                ->whereIn('school_class_id', $teacherClassIds)
                ->with([
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

        $selectedClass = $classId ? $teacherClasses->firstWhere('id', $classId) : $schoolClass;

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

        $props = [
            'tanggal' => $tanggal,
            'date' => $tanggal,
            'hariIni' => $hariIni,
            'kelasList' => $kelasList,
            'classes' => $teacherClasses,
            'classSummaries' => $classSummaries,
            'total_siswa' => $totalSiswaSekolah,
            'totalStudents' => $totalSiswaSekolah,
            'totalSiswaSeluruhnya' => $totalSiswaSekolah,
            'sudah_absen' => $totalSudahAbsen,
            'totalSudahAbsen' => $totalSudahAbsen,
            'countSudahAbsen' => $totalSudahAbsen,
            'belum_absen' => $totalBelumAbsen,
            'totalBelumAbsen' => $totalBelumAbsen,
            'countBelumAbsen' => $totalBelumAbsen,
            'jam_masuk' => $formattedJamMasuk,
            'checkInTime' => $formattedJamMasuk,
            'formattedCheckIn' => $formattedJamMasuk,
            'jam_pulang' => $formattedJamPulang,
            'checkOutTime' => $formattedJamPulang,
            'formattedCheckOut' => $formattedJamPulang,
            'batas_terlambat' => $batasTerlambat,
            'lateLimitTime' => $batasTerlambat,
            'hadir_count' => $totalHadir + $totalTerlambat,
            'countHadir' => $totalHadir + $totalTerlambat,
            'terlambat_count' => $totalTerlambat,
            'countTerlambat' => $totalTerlambat,
            'sakit_count' => $totalSakit,
            'countSakit' => $totalSakit,
            'izin_count' => $totalIzin,
            'countIzin' => $totalIzin,
            'alpha_count' => $totalAlpha,
            'countAlfa' => $totalAlpha,
            'is_holiday' => $isHoliday,
            'isHoliday' => $isHoliday,
            'holiday_description' => $holidayDescription,
            'holidayDescription' => $holidayDescription,
            'sekolah' => $schoolInfo,
            'class_id' => $classId,
            'classId' => $classId,
            'selected_class' => $selectedClass,
            'selectedClass' => $selectedClass,
            'schoolClass' => $schoolClass,
            'processed_students' => $processedStudents,
            'processedStudents' => $processedStudents,
            'studentsList' => $processedStudents,
            'attendances' => $allTodayAttendances,
            'teacher' => $teacher,
            'teacherClasses' => $teacherClasses,
            'activeTab' => $request->input('tab', $classId ? 'detail' : 'rekap_kelas'),
        ];

        return view('walikelas.absensi.index', $props);
    }

    /**
     * Halaman Detail Presensi Kelas Binaan (/guru/absensi/{schoolClass})
     */
    public function showClass(Request $request, SchoolClass|int $schoolClass): View
    {
        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $hariIni = $now->toDateString();

        [$teacher, $defaultClass, $activeYear, $teacherClasses] = $this->getTeacherAndClass($request);
        $teacherClassIds = $teacherClasses->pluck('id')->toArray();

        if (!($schoolClass instanceof SchoolClass)) {
            $schoolClass = SchoolClass::findOrFail($schoolClass);
        }

        // WAJIB: Pastikan kelas yang diakses benar-benar milik Wali Kelas ini
        if (!in_array($schoolClass->id, $teacherClassIds)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki akses ke kelas ini.');
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

        // Ambil HANYA siswa aktif di kelas binaan ini
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
            'schoolClass' => $schoolClass,
            'tanggal' => $tanggal,
            'date' => $tanggal,
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
            'processedStudents' => $studentsList,
            'allClasses' => $teacherClasses,
            'classes' => $teacherClasses,
            'teacher' => $teacher,
            'teacherClasses' => $teacherClasses,
        ];

        return view('walikelas.absensi.class', $props);
    }
}

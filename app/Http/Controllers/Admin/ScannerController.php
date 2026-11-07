<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\OperatingSchedule;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Services\TeacherCardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScannerController extends Controller
{
    /**
     * Halaman Scanner Standar Admin.
     */
    public function index(): View
    {
        Carbon::setLocale('id');
        $today = Carbon::now('Asia/Jakarta');
        $dateString = $today->toDateString();

        $activeYear = AcademicYear::getActive();

        $schoolName = Setting::getSchoolName();
        $scheduleTimes = OperatingSchedule::timesForDate($today);
        $checkInTime = $scheduleTimes['check_in_time'] ?? Setting::getCheckInTime();
        $lateLimitTime = $scheduleTimes['late_limit_time'] ?? Setting::getLateLimitTime();
        $checkOutTime = $scheduleTimes['check_out_time'] ?? Setting::getCheckOutTime();

        $recentScans = Attendance::with(['student.schoolClass'])
            ->where('date', $dateString)
            ->whereNotNull('check_in')
            ->latest('updated_at')
            ->take(10)
            ->get();

        return view('admin.scanner', compact(
            'today',
            'schoolName',
            'checkInTime',
            'lateLimitTime',
            'checkOutTime',
            'recentScans'
        ));
    }

    /**
     * Mode Gerbang / Kiosk Khusus Satpam & Penjaga Gerbang.
     */
    public function kiosk(): View
    {
        Carbon::setLocale('id');
        $today = Carbon::now('Asia/Jakarta');
        $dateString = $today->toDateString();

        $activeYear = AcademicYear::getActive();
        $schoolName = Setting::getSchoolName();
        $scheduleTimes = OperatingSchedule::timesForDate($today);
        $checkInTime = $scheduleTimes['check_in_time'] ?? Setting::getCheckInTime();
        $lateLimitTime = $scheduleTimes['late_limit_time'] ?? Setting::getLateLimitTime();
        $checkOutTime = $scheduleTimes['check_out_time'] ?? Setting::getCheckOutTime();

        $recentScans = Attendance::with(['student.schoolClass'])
            ->where('date', $dateString)
            ->where(function ($q) {
                $q->whereNotNull('check_in')->orWhereNotNull('check_out');
            })
            ->latest('updated_at')
            ->take(12)
            ->get();

        return view('admin.kiosk', compact(
            'today',
            'schoolName',
            'checkInTime',
            'lateLimitTime',
            'checkOutTime',
            'activeYear',
            'recentScans'
        ));
    }

    /**
     * Proses Pemindaian QR / Barcode Siswa (Check-in / Check-out).
     * Terproteksi transaksi atomik & row lock anti-race condition.
     */
    public function processScan(Request $request): JsonResponse
    {
        $request->validate([
            'qr_token' => 'required|string',
        ]);

        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $todayDate = $now->toDateString();
        $currentTime = $now->toTimeString();

        $cleanToken = trim($request->qr_token);
        $lookupKey = Student::lookupKey($cleanToken);

        // Token guru tanpa NIP valid selalu berakhiran "_guru". Token ini
        // diperiksa lebih dulu dan tidak pernah dianggap sebagai token siswa.
        if (str_ends_with($lookupKey, '_guru')) {
            $teacher = Teacher::orderBy('id')->get()->first(function (Teacher $candidate) use ($lookupKey) {
                if (TeacherCardService::normalizeNip($candidate->nip) !== null) {
                    return false;
                }

                return Student::lookupKey($candidate->qrValue()) === $lookupKey;
            });

            if ($teacher) {
                $teacherResult = $this->processTeacherScan($request, $teacher, $cleanToken, $todayDate, $currentTime);
                return response()->json($teacherResult['response'], $teacherResult['status_code'] ?? 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Kartu tidak dikenali atau data siswa/guru tidak ditemukan!',
            ], 404);
        }

        // NISN tetap dicocokkan persis; NISN ganda tetap ditolak.
        $nisnMatches = Student::with('schoolClass')
            ->where('nisn', $cleanToken)
            ->orderBy('id')
            ->get();

        $student = null;
        if ($nisnMatches->count() === 1) {
            $student = $nisnMatches->first();
        } elseif ($nisnMatches->count() > 1) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'NISN dipakai lebih dari satu siswa, scan QR kartu.',
            ], 409);
        }

        if (!$student) {
            // Token Nama_Kelas hanya berlaku untuk siswa jalur fallback:
            // NISN kosong atau NISN dipakai lebih dari satu siswa.
            $duplicateNisn = Student::query()
                ->select('nisn')
                ->whereNotNull('nisn')
                ->where('nisn', '!=', '')
                ->groupBy('nisn')
                ->havingRaw('COUNT(*) > 1');

            $student = Student::with('schoolClass')
                ->where(function ($query) use ($duplicateNisn) {
                    $query->whereNull('nisn')
                        ->orWhere('nisn', '')
                        ->orWhereIn('nisn', $duplicateNisn);
                })
                ->orderBy('id')
                ->get()
                ->first(fn (Student $candidate) => Student::lookupKey($candidate->qrValue()) === $lookupKey);
        }

        if (!$student) {
            $teacher = Teacher::where('nip', $cleanToken)->orderBy('id')->first();

            if ($teacher) {
                $teacherResult = $this->processTeacherScan($request, $teacher, $cleanToken, $todayDate, $currentTime);
                return response()->json($teacherResult['response'], $teacherResult['status_code'] ?? 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Kartu tidak dikenali atau data siswa/guru tidak ditemukan!',
            ], 404);
        }

        $activeYear = AcademicYear::getActive();
        $academicYearId = $activeYear ? $activeYear->id : null;

        // Presensi hanya dibuka Senin-Jumat dan pada hari yang bukan libur.
        $scheduleTimes = OperatingSchedule::timesForDate($now);

        if ($scheduleTimes === null) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Hari ini tidak ada jadwal presensi (Sabtu/Minggu).',
            ], 400);
        }

        if (Holiday::isHoliday($todayDate)) {
            $holidayDescription = Holiday::getHolidayDescription($todayDate);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Hari ini hari libur' . ($holidayDescription ? ': ' . $holidayDescription : '') . '. Presensi tidak dibuka.',
            ], 400);
        }

        $lateLimitTime = $scheduleTimes['late_limit_time'];
        $checkInStartTime = $scheduleTimes['check_in_time'];
        $checkOutTime = $scheduleTimes['check_out_time'];
        $checkOutLimitTime = $scheduleTimes['check_out_limit_time'];
        $currentMinutes = $this->timeToMinutes($currentTime);

        // Catatan: pengecekan "sudah presensi hari ini" TIDAK lagi menolak
        // seluruh scan. Scan kedua pada hari yang sama adalah presensi pulang
        // dan validasinya dilakukan di dalam transaksi di bawah.
        $result = DB::transaction(function () use (
            $student, $todayDate, $currentTime, $currentMinutes, $academicYearId,
            $lateLimitTime, $checkInStartTime, $checkOutTime, $checkOutLimitTime
        ) {
            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('date', $todayDate)
                ->lockForUpdate()
                ->first();

            if (!$attendance) {
                $attendance = new Attendance([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYearId,
                    'date' => $todayDate,
                ]);
            }

            // KASUS 1: Presensi Masuk (Check-In)
            if (empty($attendance->check_in)) {
                /*
                 * Penentuan status terlambat memakai SATU sumber kebenaran:
                 * Attendance::evaluateCheckInTime() - fungsi yang sama dengan
                 * dipakai presensi manual. Scan <= batas terlambat = Hadir
                 * (Tepat Waktu), scan > batas = Hadir + penanda Terlambat.
                 * Yang tersimpan tetap status 'Hadir' supaya seluruh query
                 * kehadiran (Dashboard, Rekap, Kehadiran) konsisten; status
                 * "Terlambat" dibaca lewat accessor effective_status.
                 */
                $evaluasi = Attendance::evaluateCheckInTime($currentTime, $lateLimitTime, $checkInStartTime);

                // Scan sebelum jam masuk per hari ditolak (db_status null).
                if ($evaluasi['db_status'] === null) {
                    return [
                        'status_code' => 400,
                        'should_notify_wa' => false,
                        'response' => [
                            'success' => false,
                            'status' => 'error',
                            'message' => 'Presensi belum dibuka. Presensi dimulai pukul ' . substr($checkInStartTime, 0, 5) . ' WIB.',
                            'student' => [
                                'name' => $student->name,
                                'nisn' => $student->nisn,
                                'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                                'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                            ],
                        ],
                    ];
                }

                $attendance->check_in = $currentTime;
                $attendance->status = Attendance::STATUS_HADIR;
                $attendance->time_remark = $evaluasi['time_remark'];
                $attendance->is_late = (bool) $evaluasi['is_late'];
                $attendance->late_minutes = (int) $evaluasi['late_minutes'];

                $isLate = (bool) $evaluasi['is_late'];
                $lateMinutes = (int) $evaluasi['late_minutes'];
                $timeRemark = $evaluasi['time_remark'];
                $displayRemark = $evaluasi['display_remark'];

                try {
                    $attendance->save();
                } catch (\Illuminate\Database\QueryException $e) {
                    // Race condition: request lain menyisipkan baris
                    // (student_id, date) yang sama lebih dulu sehingga UNIQUE
                    // constraint menolak simpan ini. Kembalikan pesan yang sama
                    // seperti pemeriksaan aplikasi, bukan error 500.
                    return [
                        'status_code' => 400,
                        'should_notify_wa' => false,
                        'response' => [
                            'success' => false,
                            'status' => 'error',
                            'message' => 'Siswa atas nama ' . $student->name . ' sudah melakukan presensi hari ini.',
                            'student' => [
                                'name' => $student->name,
                                'nisn' => $student->nisn,
                                'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                                'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                            ],
                        ],
                    ];
                }

                return [
                    'status_code' => 200,
                    'should_notify_wa' => true,
                    'attendance' => $attendance,
                    'response' => [
                        'success' => true,
                        'message' => 'Presensi Masuk Berhasil!',
                        'type' => 'check_in',
                        'time' => substr($currentTime, 0, 8),
                        'time_short' => substr($currentTime, 0, 5),
                        'remark' => $timeRemark,
                        'display_remark' => $displayRemark,
                        'is_late' => $isLate,
                        'late_minutes' => $lateMinutes,
                        'student' => [
                            'name' => $student->name,
                            'nisn' => $student->nisn,
                            'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                            'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                        ],
                    ],
                ];
            }

            // KASUS 2: Presensi Pulang (Check-Out)
            // Jika sudah presensi masuk, tapi belum presensi pulang
            if (empty($attendance->check_out)) {
                $checkOutMinutes = $this->timeToMinutes($checkOutTime);
                $checkOutLimitMinutes = $this->timeToMinutes($checkOutLimitTime);

                // Pulang hanya boleh mulai Jam Pulang per hari.
                if ($currentMinutes < $checkOutMinutes) {
                    return [
                        'status_code' => 400,
                        'should_notify_wa' => false,
                        'response' => [
                            'success' => false,
                            'message' => 'Belum waktunya presensi pulang. Jam pulang pukul ' . substr($checkOutTime, 0, 5) . ' WIB.',
                            'student' => [
                                'name' => $student->name,
                                'nisn' => $student->nisn,
                                'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                                'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                            ],
                        ],
                    ];
                }

                // Setelah Batas Waktu Pulang, presensi pulang ditolak.
                if ($currentMinutes > $checkOutLimitMinutes) {
                    return [
                        'status_code' => 400,
                        'should_notify_wa' => false,
                        'response' => [
                            'success' => false,
                            'message' => 'Batas waktu presensi pulang sudah lewat (batas pukul ' . substr($checkOutLimitTime, 0, 5) . ' WIB).',
                            'student' => [
                                'name' => $student->name,
                                'nisn' => $student->nisn,
                                'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                                'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                            ],
                        ],
                    ];
                }

                // Cek cooldown proteksi dobel tap dalam 2 menit
                $checkInSeconds = strtotime($currentTime) - strtotime($attendance->check_in);
                if ($checkInSeconds >= 0 && $checkInSeconds < 120) {
                    return [
                        'status_code' => 400,
                        'should_notify_wa' => false,
                        'response' => [
                            'success' => false,
                            'message' => "Siswa baru saja melakukan presensi masuk pada " . substr($attendance->check_in, 0, 5) . " WIB. Harap tunggu beberapa saat.",
                            'student' => [
                                'name' => $student->name,
                                'nisn' => $student->nisn,
                                'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                                'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                            ],
                        ],
                    ];
                }

                // Catat presensi pulang
                $attendance->check_out = $currentTime;
                $attendance->save();

                return [
                    'status_code' => 200,
                    'should_notify_wa' => false,
                    'attendance' => $attendance,
                    'response' => [
                        'success' => true,
                        'message' => 'Presensi Pulang Berhasil!',
                        'type' => 'check_out',
                        'time' => substr($currentTime, 0, 8),
                        'time_short' => substr($currentTime, 0, 5),
                        'remark' => 'Pulang',
                        'display_remark' => 'Presensi Pulang Tercatat',
                        'is_late' => false,
                        'late_minutes' => 0,
                        'student' => [
                            'name' => $student->name,
                            'nisn' => $student->nisn,
                            'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                            'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                        ],
                    ],
                ];
            }

            // KASUS 3: Siswa sudah lengkap Masuk & Pulang
            return [
                'status_code' => 400,
                'should_notify_wa' => false,
                'response' => [
                    'success' => false,
                    'message' => "Siswa sudah menyelesaikan presensi Masuk (" . substr($attendance->check_in, 0, 5) . " WIB) dan Pulang (" . substr($attendance->check_out, 0, 5) . " WIB) hari ini.",
                    'student' => [
                        'name' => $student->name,
                        'nisn' => $student->nisn,
                        'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                        'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                    ],
                ],
            ];
        });

        return response()->json($result['response'], $result['status_code'] ?? 200);
    }

    /**
     * Ubah jam "HH:MM" atau "HH:MM:SS" menjadi menit sejak 00:00.
     */
    protected function timeToMinutes(string $time): int
    {
        return ((int) substr($time, 0, 2)) * 60 + (int) substr($time, 3, 2);
    }

    /**
     * Proses pemindaian QR Kartu Guru (Check-in).
     * Format respons kompatibel dengan UI scanner siswa (body.student/teacher, time_short, is_late, late_minutes, dll.)
     * agar layar scanner/kiosk tidak perlu diubah.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Teacher  $teacher
     * @param  string  $cleanToken
     * @param  string  $todayDate
     * @param  string  $currentTime
     * @return array
     */
    protected function processTeacherScan(Request $request, Teacher $teacher, string $cleanToken, string $todayDate, string $currentTime): array
    {
        $activeYear = AcademicYear::getActive();
        if (!$activeYear) {
            return [
                'status_code' => 400,
                'should_notify_wa' => false,
                'response' => [
                    'success' => false,
                    'message' => 'Tahun ajaran aktif belum ditetapkan. Presensi guru tidak dapat disimpan.',
                    'teacher' => $this->teacherResponseArray($teacher),
                ],
            ];
        }

        // Anti-double scan: cek apakah guru sudah presensi hari ini.
        $existingAttendance = TeacherAttendance::where('teacher_id', $teacher->id)
            ->whereDate('date', $todayDate)
            ->first();

        if ($existingAttendance) {
            return [
                'status_code' => 400,
                'should_notify_wa' => false,
                'response' => [
                    'success' => false,
                    'message' => 'Guru atas nama '.$teacher->name.' sudah melakukan presensi hari ini.',
                    'teacher' => $this->teacherResponseArray($teacher),
                ],
            ];
        }

        // Evaluasi jam masuk (tepat waktu / terlambat) — sama logika dengan siswa.
        $lateLimitTime = Setting::getLateLimitTime();
        $eval = Attendance::evaluateCheckInTime($currentTime, $lateLimitTime);

        $className = '-';
        $class = $teacher->schoolClass;
        if ($class) {
            $className = $class->name;
        } elseif ($teacher->schoolClasses()->exists()) {
            $className = $teacher->schoolClasses()->first()->name;
        }

        // Status baku untuk tabel teacher_attendances (enum sudah termasuk 'Terlambat').
        $dbStatus = $eval['is_late'] ? Attendance::STATUS_TERLAMBAT : Attendance::STATUS_HADIR;
        $timeRemark = $eval['time_remark']; // 'Tepat Waktu' atau 'Terlambat'

        $teacherAttendance = TeacherAttendance::create([
            'teacher_id' => $teacher->id,
            'date' => $todayDate,
            'status' => $dbStatus,
            'time_remark' => $timeRemark,
        ]);

        return [
            'status_code' => 200,
            'should_notify_wa' => false,
            'response' => [
                'success' => true,
                'message' => 'Presensi Masuk Berhasil!',
                'type' => 'check_in',
                'time' => substr($currentTime, 0, 8),
                'time_short' => substr($currentTime, 0, 5),
                'remark' => $timeRemark,
                'display_remark' => $eval['display_remark'],
                'is_late' => (bool) $eval['is_late'],
                'late_minutes' => (int) $eval['late_minutes'],
                'student' => [
                    'name' => $teacher->name,
                    'nisn' => $teacher->nip ?: '-',
                    'class' => $className,
                    'photo' => $teacher->photo ? asset('storage/' . $teacher->photo) : null,
                ],
                'teacher' => $this->teacherResponseArray($teacher),
            ],
        ];
    }

    /**
     * Susun array response data guru untuk konsistensi antara check-in dan anti-double-scan.
     */
    protected function teacherResponseArray(Teacher $teacher): array
    {
        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'nip' => $teacher->nip ?: '-',
        ];
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AcademicYear;
use App\Models\Attendance;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ScannerController extends Controller
{
    private const WEEKEND_MESSAGE = 'Hari ini tidak ada jadwal presensi (Sabtu/Minggu).';

    /**
     * Sabtu/Minggu (zona Asia/Jakarta) = tidak ada jadwal presensi.
     */
    private function isWeekend(string $date): bool
    {
        return Carbon::parse($date, 'Asia/Jakarta')->isWeekend();
    }

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
        $checkInTime = Setting::getCheckInTime();
        $lateLimitTime = Setting::getLateLimitTime();
        $checkOutTime = Setting::getCheckOutTime();

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
        $checkInTime = Setting::getCheckInTime();
        $lateLimitTime = Setting::getLateLimitTime();
        $checkOutTime = Setting::getCheckOutTime();

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

        // Token berawalan "GURU-" (GURU-<NIP> atau GURU-<kode> untuk guru tanpa NIP).
        // Bila tidak ada guru yang cocok, lanjut ke pemeriksaan lain di bawah.
        if (preg_match('/^GURU[\s:\-_]/i', $cleanToken)) {
            $teacher = TeacherCardService::findTeacherByScan($cleanToken);

            if ($teacher) {
                $teacherResult = $this->processTeacherScan($request, $teacher, $cleanToken, $todayDate, $currentTime);
                return response()->json($teacherResult['response'], $teacherResult['status_code'] ?? 200);
            }
        }

        // Token guru tanpa NIP valid selalu berakhiran "_guru". Token ini
        // diperiksa lebih dulu dan tidak pernah dianggap sebagai token siswa.
        if (str_ends_with($lookupKey, '_guru')) {
            $teacher = TeacherCardService::findTeacherByScan($cleanToken);

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

        // Kartu yang berisi qr_token (UUID) siswa.
        if (!$student && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $cleanToken)) {
            $student = Student::with('schoolClass')->where('qr_token', $cleanToken)->first();
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
            $teacher = Teacher::where('nip', $cleanToken)->orderBy('id')->first()
                ?? TeacherCardService::findTeacherByScan($cleanToken);

            if ($teacher) {
                $teacherResult = $this->processTeacherScan($request, $teacher, $cleanToken, $todayDate, $currentTime);
                return response()->json($teacherResult['response'], $teacherResult['status_code'] ?? 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Kartu tidak dikenali atau data siswa/guru tidak ditemukan!',
            ], 404);
        }

        // Akhir pekan (Sabtu/Minggu) tidak ada jadwal presensi.
        if ($this->isWeekend($todayDate)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => self::WEEKEND_MESSAGE,
                'student' => [
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                    'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                ],
            ], 400);
        }

        $activeYear = AcademicYear::getActive();
        $academicYearId = $activeYear ? $activeYear->id : null;

        // Jadwal PER HARI (Senin, Selasa, dst.) dari Pengaturan; bila belum ada
        // barisnya, jatuh ke setting tunggal lama.
        $schedule = OperatingSchedule::timesForDate($now) ?? [];
        $checkInOpenTime = $schedule['check_in_time'] ?? Setting::getCheckInTime();
        $lateLimitTime = $schedule['late_limit_time'] ?? Setting::getLateLimitTime();
        $checkOutThreshold = $schedule['check_out_time'] ?? Setting::getCheckOutTime();
        $checkOutLimit = $schedule['check_out_limit_time']
            ?? date('H:i', strtotime($checkOutThreshold . ' +60 minutes'));

        // Anti-double scan: tolak HANYA bila catatan hari ini bukan presensi
        // hadir (mis. Sakit/Izin/Alfa tanpa jam masuk). Siswa yang sudah scan
        // masuk TIDAK boleh ditolak di sini, supaya bisa scan pulang.
        // (Sebelumnya semua catatan ditolak sehingga presensi pulang
        // tidak pernah tercapai.)
        $existingAttendance = Attendance::where('student_id', $student->id)
            ->whereDate('date', $todayDate)
            ->first();

        if ($existingAttendance && empty($existingAttendance->check_in)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Siswa atas nama ' . $student->name . ' sudah melakukan presensi hari ini.',
                'student' => [
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                    'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                ],
            ], 400);
        }

        $result = DB::transaction(function () use (
            $student, $todayDate, $currentTime, $academicYearId, $lateLimitTime, $checkOutThreshold, $checkOutLimit, $checkInOpenTime
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
                // Sudah memasuki jendela pulang tapi belum pernah presensi masuk:
                // TOLAK. Hanya siswa yang sudah presensi masuk yang boleh pulang,
                // dan scan di jam pulang tidak boleh diam-diam jadi presensi masuk.
                if (substr($currentTime, 0, 5) >= substr((string) $checkOutThreshold, 0, 5)) {
                    return [
                        'status_code' => 400,
                        'should_notify_wa' => false,
                        'response' => [
                            'success' => false,
                            'status' => 'error',
                            'message' => 'Siswa belum presensi masuk hari ini.',
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
                /*
                 * Penentuan status terlambat memakai SATU sumber kebenaran:
                 * Attendance::evaluateCheckInTime() - fungsi yang sama dengan
                 * dipakai presensi manual. Scan <= batas terlambat = Hadir
                 * (Tepat Waktu), scan > batas = Hadir + penanda Terlambat.
                 * Yang tersimpan tetap status 'Hadir' supaya seluruh query
                 * kehadiran (Dashboard, Rekap, Kehadiran) konsisten; status
                 * "Terlambat" dibaca lewat accessor effective_status.
                 */
                $evaluasi = Attendance::evaluateCheckInTime($currentTime, $lateLimitTime, $checkInOpenTime);

                // Scan SEBELUM jam buka presensi: tolak, jangan simpan apa pun.
                // (db_status null = sebelum jam buka; time_remark berisi pesan
                // "Presensi belum dibuka. Presensi dimulai pukul HH:MM."
                // yang terlalu panjang untuk kolom time_remark.)
                if (($evaluasi['db_status'] ?? null) === null) {
                    return [
                        'status_code' => 400,
                        'should_notify_wa' => false,
                        'response' => [
                            'success' => false,
                            'status' => 'error',
                            'message' => $evaluasi['time_remark'],
                            'student' => [
                                'name' => $student->name,
                                'nisn' => $student->nisn,
                                'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                                'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                            ],
                        ],
                    ];
                }

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
                    // HANYA pelanggaran UNIQUE (student_id, date) yang berarti
                    // "sudah presensi" (race condition). Error DB lain (kolom
                    // tidak ada, NOT NULL, enum tidak cocok, dll.) JANGAN
                    // disamarkan jadi pesan duplikat: dicatat ke log dan
                    // dikembalikan sebagai kegagalan simpan yang jujur.
                    $sqlState = $e->errorInfo[0] ?? null;
                    $driverCode = (int) ($e->errorInfo[1] ?? 0);
                    $isDuplicate = $sqlState === '23000' && in_array($driverCode, [1062, 19, 2067, 1555], true);

                    if (!$isDuplicate) {
                        \Log::error('Scan presensi gagal disimpan: ' . $e->getMessage(), [
                            'student_id' => $student->id,
                            'date' => $todayDate,
                            'time' => $currentTime,
                            'academic_year_id' => $academicYearId,
                        ]);

                        return [
                            'status_code' => 500,
                            'should_notify_wa' => false,
                            'response' => [
                                'success' => false,
                                'status' => 'error',
                                'message' => 'Presensi gagal disimpan karena kesalahan sistem. Hubungi admin.',
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

                // Jendela pulang sesuai Pengaturan hari ini:
                // [Jam Pulang, Batas Waktu Pulang].
                $nowHm = substr($currentTime, 0, 5);
                $openHm = substr((string) $checkOutThreshold, 0, 5);
                $closeHm = substr((string) $checkOutLimit, 0, 5);
                $studentInfo = [
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                    'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                ];

                if ($nowHm < $openHm) {
                    return [
                        'status_code' => 400,
                        'should_notify_wa' => false,
                        'response' => [
                            'success' => false,
                            'status' => 'error',
                            'message' => "Presensi pulang belum dibuka. Dimulai pukul {$openHm} WIB.",
                            'student' => $studentInfo,
                        ],
                    ];
                }

                if ($nowHm > $closeHm) {
                    return [
                        'status_code' => 400,
                        'should_notify_wa' => false,
                        'response' => [
                            'success' => false,
                            'status' => 'error',
                            'message' => "Batas presensi pulang ({$closeHm} WIB) sudah lewat.",
                            'student' => $studentInfo,
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

            // KASUS 3: Siswa sudah lengkap Masuk & Pulang.
            // Ini BUKAN kegagalan (presensinya sudah lengkap), jadi dikirim
            // sebagai sukses (HTTP 200, success=true) supaya ikon di layar
            // scanner hijau, bukan X merah.
            return [
                'status_code' => 200,
                'should_notify_wa' => false,
                'response' => [
                    'success' => true,
                    'status' => 'completed',
                    'type' => 'completed',
                    'time' => '',
                    'time_short' => '',
                    'remark' => 'SISWA SUDAH PRESENSI MASUK DAN PULANG.',
                    'display_remark' => 'SISWA SUDAH PRESENSI MASUK DAN PULANG.',
                    'is_late' => false,
                    'late_minutes' => 0,
                    'message' => 'SISWA SUDAH PRESENSI MASUK DAN PULANG.',
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
        // Akhir pekan (Sabtu/Minggu) tidak ada jadwal presensi.
        if ($this->isWeekend($todayDate)) {
            return [
                'status_code' => 400,
                'should_notify_wa' => false,
                'response' => [
                    'success' => false,
                    'status' => 'error',
                    'message' => self::WEEKEND_MESSAGE,
                    'teacher' => $this->teacherResponseArray($teacher),
                ],
            ];
        }

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

        // Guru TIDAK mengenal status terlambat dan tidak memakai jam buka/batas
        // terlambat siswa: scan pada jam berapa pun dicatat Hadir, dan jam scan
        // disimpan sebagai jam masuk.

        $className = '-';
        $class = $teacher->schoolClass;
        if ($class) {
            $className = $class->name;
        } elseif ($teacher->schoolClasses()->exists()) {
            $className = $teacher->schoolClasses()->first()->name;
        }

        // Status guru selalu Hadir (tidak ada Terlambat untuk guru).
        $dbStatus = TeacherAttendance::STATUS_HADIR;
        $timeRemark = TeacherAttendance::REMARK_TEPAT_WAKTU;

        // Kolom wajib (selalu ada di tabel).
        $attendanceData = [
            'teacher_id' => $teacher->id,
            'date' => $todayDate,
            'status' => $dbStatus,
            'time_remark' => $timeRemark,
        ];

        // Kolom opsional: hanya diisi bila tabel teacher_attendances memang punya kolomnya,
        // supaya tidak error "Unknown column" di database yang skemanya berbeda.
        $optionalColumns = [
            'academic_year_id' => $activeYear->id,
            'check_in' => $currentTime,
        ];
        foreach ($optionalColumns as $column => $value) {
            if (Schema::hasColumn('teacher_attendances', $column)) {
                $attendanceData[$column] = $value;
            }
        }

        $teacherAttendance = TeacherAttendance::create($attendanceData);

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
                'display_remark' => 'Hadir',
                'is_late' => false,
                'late_minutes' => 0,
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
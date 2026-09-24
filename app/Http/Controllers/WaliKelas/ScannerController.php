<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppAttendanceNotificationJob;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScannerController extends Controller
{
    /**
     * Dapatkan Guru dan Kelas yang diampu oleh User login.
     */
    protected function getTeacherAndClass(): array
    {
        $user = Auth::user();
        $teacher = Teacher::where('user_id', $user->id)->first();
        $activeYear = AcademicYear::getActive();

        $schoolClass = null;
        if ($teacher) {
            $schoolClass = SchoolClass::where('teacher_id', $teacher->id)
                ->when($activeYear, fn($q) => $q->where('academic_year_id', $activeYear->id))
                ->first();

            if (!$schoolClass) {
                $schoolClass = SchoolClass::where('teacher_id', $teacher->id)->first();
            }
        }

        return [$teacher, $schoolClass, $activeYear];
    }

    /**
     * Tampilan Scanner Presensi Masuk / Mode Gerbang Guru (Wali Kelas).
     */
    public function index(): View
    {
        Carbon::setLocale('id');
        $today = Carbon::now('Asia/Jakarta');
        $dateString = $today->toDateString();

        [$teacher, $schoolClass, $activeYear] = $this->getTeacherAndClass();

        $schoolName = Setting::getSchoolName();
        $checkInTime = Setting::getCheckInTime();
        $lateLimitTime = Setting::getLateLimitTime();
        $checkOutTime = Setting::getCheckOutTime();

        $recentScans = collect();
        if ($schoolClass) {
            $recentScans = Attendance::with(['student.schoolClass'])
                ->where('date', $dateString)
                ->whereNotNull('check_in')
                ->whereHas('student', fn($q) => $q->where('school_class_id', $schoolClass->id))
                ->latest('updated_at')
                ->take(10)
                ->get();
        }

        return view('walikelas.scanner', compact(
            'today',
            'schoolName',
            'checkInTime',
            'lateLimitTime',
            'checkOutTime',
            'schoolClass',
            'teacher',
            'activeYear',
            'recentScans'
        ));
    }

    /**
     * Simpan / Proses Scan Presensi Masuk (AJAX).
     * Terproteksi transaksi atomik & row lock anti-race condition.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'qr_token' => 'required|string',
        ]);

        [$teacher, $schoolClass, $activeYear] = $this->getTeacherAndClass();

        if (!$schoolClass) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum ditetapkan sebagai wali kelas pada tahun ajaran aktif.',
            ], 403);
        }

        Carbon::setLocale('id');
        $now = Carbon::now('Asia/Jakarta');
        $todayDate = $now->toDateString();
        $currentTime = $now->toTimeString();
        $cleanToken = trim($request->qr_token);

        // Cari data siswa
        $student = Student::with('schoolClass')
            ->where(function ($query) use ($cleanToken) {
                $query->where('qr_token', $cleanToken)
                      ->orWhere('nisn', $cleanToken)
                      ->orWhere('nis', $cleanToken);
            })
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak terdaftar atau data siswa tidak ditemukan!',
            ], 404);
        }

        // Validasi: Siswa harus benar-benar berada di kelas binaan guru ini
        $teacherClasses = $teacher ? $teacher->schoolClasses : collect();
        $isTeacherStudent = ($student->school_class_id === $schoolClass->id) 
            || ($teacherClasses->isNotEmpty() && $teacherClasses->pluck('id')->contains($student->school_class_id));

        if (!$isTeacherStudent) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Siswa bukan dari kelas Anda.',
            ], 403);
        }

        // Validasi ganda anti-double scan lintas role (Admin Gerbang & Guru Kelas) sebelum DB::transaction
        $existingAttendance = Attendance::where('student_id', $student->id)
            ->where('date', $todayDate)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Siswa atas nama ' . $student->name . ' sudah melakukan presensi hari ini.',
                'student' => [
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'class' => $schoolClass->name,
                    'photo' => $student->photo ?? null,
                ],
            ], 400);
        }

        $lateLimitTime = Setting::getLateLimitTime();

        $result = DB::transaction(function () use (
            $student, $schoolClass, $activeYear, $todayDate, $currentTime, $lateLimitTime
        ) {
            $attendance = Attendance::where('student_id', $student->id)
                ->whereDate('date', $todayDate)
                ->lockForUpdate()
                ->first();

            if (!$attendance) {
                $attendance = new Attendance([
                    'student_id' => $student->id,
                    'academic_year_id' => $activeYear?->id,
                    'date' => $todayDate,
                ]);
            }

            if (!empty($attendance->check_in)) {
                $formattedTime = substr($attendance->check_in, 0, 5);
                return [
                    'status_code' => 400,
                    'should_notify_wa' => false,
                    'response' => [
                        'success' => false,
                        'message' => "Siswa '{$student->name}' sudah melakukan presensi masuk hari ini pada pukul {$formattedTime} WIB (" . ($attendance->time_remark ?? 'Hadir') . ").",
                        'student' => [
                            'name' => $student->name,
                            'nis' => $student->nis,
                            'class' => $schoolClass->name,
                            'photo' => $student->photo ?? null,
                        ],
                    ],
                ];
            }

            $attendance->check_in = $currentTime;
            $attendance->status = 'Hadir';

            $lateMinutes = 0;
            $isLate = strtotime($currentTime) > strtotime($lateLimitTime);

            if ($isLate) {
                $diff = Carbon::parse($currentTime)->diffInMinutes(Carbon::parse($lateLimitTime), false);
                $lateMinutes = $diff < 0 ? abs($diff) : 0;
                $timeRemark = 'Terlambat';
                $displayRemark = "Terlambat (+{$lateMinutes} menit)";
            } else {
                $timeRemark = 'Tepat Waktu';
                $displayRemark = 'Tepat Waktu';
            }

            $attendance->time_remark = $timeRemark;
            $attendance->save();

            return [
                'status_code' => 200,
                'should_notify_wa' => true,
                'attendance' => $attendance,
                'response' => [
                    'success' => true,
                    'message' => 'Presensi Masuk Berhasil!',
                    'type' => 'check_in',
                    'time' => strlen($currentTime) == 5 ? $currentTime . ':00' : substr($currentTime, 0, 8),
                    'time_short' => substr($currentTime, 0, 5),
                    'remark' => $timeRemark,
                    'display_remark' => $displayRemark,
                    'is_late' => $isLate,
                    'late_minutes' => $lateMinutes,
                    'student' => [
                        'name' => $student->name,
                        'nis' => $student->nis,
                        'class' => $schoolClass->name,
                        'photo' => $student->photo ?? null,
                    ],
                ],
            ];
        });

        // Notifikasi WhatsApp Otomatis ke Orang Tua dikirim secara Asynchronous via Queue Job
        if (!empty($result['should_notify_wa']) && isset($result['attendance'])) {
            SendWhatsAppAttendanceNotificationJob::dispatch($student, $result['attendance']);
        }

        return response()->json($result['response'], $result['status_code'] ?? 200);
    }
}
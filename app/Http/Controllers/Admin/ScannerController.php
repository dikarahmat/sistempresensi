<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Jobs\SendWhatsAppAttendanceNotificationJob;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
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
                'message' => 'Kartu tidak dikenali atau data siswa tidak ditemukan!',
            ], 404);
        }

        $activeYear = AcademicYear::getActive();
        $academicYearId = $activeYear ? $activeYear->id : null;

        $lateLimitTime = Setting::getLateLimitTime();
        $checkOutThreshold = Setting::getCheckOutTime();

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
                    'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                    'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                ],
            ], 400);
        }

        $result = DB::transaction(function () use (
            $student, $todayDate, $currentTime, $academicYearId, $lateLimitTime, $checkOutThreshold
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
                $attendance->check_in = $currentTime;
                $attendance->status = 'Hadir';

                $lateMinutes = 0;
                $isLate = strtotime($currentTime) > strtotime($lateLimitTime);

                if ($isLate) {
                    $diff = Carbon::parse($currentTime)->diffInMinutes(Carbon::parse($lateLimitTime), false);
                    $lateMinutes = $diff < 0 ? abs($diff) : 0;
                    $timeRemark = 'Terlambat';
                    $displayRemark = "Terlambat (+{$lateMinutes} mnt)";
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
                        'time' => substr($currentTime, 0, 8),
                        'time_short' => substr($currentTime, 0, 5),
                        'remark' => $timeRemark,
                        'display_remark' => $displayRemark,
                        'is_late' => $isLate,
                        'late_minutes' => $lateMinutes,
                        'student' => [
                            'name' => $student->name,
                            'nis' => $student->nis,
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
                                'nis' => $student->nis,
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
                            'nis' => $student->nis,
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
                        'nis' => $student->nis,
                        'class' => $student->schoolClass ? $student->schoolClass->name : '-',
                        'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                    ],
                ],
            ];
        });

        // Notifikasi WhatsApp ke Orang Tua dikirim secara Asynchronous via Queue Job SETELAH DB::transaction selesai
        if (!empty($result['should_notify_wa']) && isset($result['attendance'])) {
            SendWhatsAppAttendanceNotificationJob::dispatch($student, $result['attendance']);
        }

        return response()->json($result['response'], $result['status_code'] ?? 200);
    }
}
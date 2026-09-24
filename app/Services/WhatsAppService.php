<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Kirim notifikasi WhatsApp ke nomor HP orang tua/wali siswa saat presensi masuk.
     */
    public static function sendAttendanceNotification($student, $attendance): void
    {
        try {
            $status = Setting::get('whatsapp_gateway_status', 'inactive');
            if ($status !== 'active') {
                return;
            }

            $token = Setting::get('whatsapp_api_token', '');
            if (empty($token) || empty($student->parent_phone)) {
                return;
            }

            $parentPhone = preg_replace('/[^0-9]/', '', $student->parent_phone);
            if (str_starts_with($parentPhone, '0')) {
                $parentPhone = '62' . substr($parentPhone, 1);
            }

            $schoolName = Setting::getSchoolName();
            $remark = $attendance->time_remark ?? 'Tepat Waktu';
            $jam = substr($attendance->check_in, 0, 5);
            $tanggal = date('d-m-Y');
            $namaSiswa = $student->name;
            $kelas = $student->schoolClass ? $student->schoolClass->name : '-';

            $message = "*NOTIFIKASI PRESENSI SISWA*\n";
            $message .= "{$schoolName}\n\n";
            $message .= "Yth. Orang Tua / Wali dari ananda:\n";
            $message .= "• *Nama:* {$namaSiswa}\n";
            $message .= "• *Kelas:* {$kelas}\n";
            $message .= "• *Waktu:* {$jam} WIB\n";
            $message .= "• *Status:* Hadir ({$remark})\n\n";
            $message .= "Terima kasih atas perhatian dan kerja sama Bapak/Ibu.";

            $gatewayUrl = Setting::get('wa_gateway_url', 'https://api.fonnte.com/send');

            Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(5)->post($gatewayUrl, [
                'target' => $parentPhone,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim notifikasi WhatsApp: ' . $e->getMessage());
        }
    }
}

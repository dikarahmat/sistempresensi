<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\Student;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppAttendanceNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Jumlah percobaan maksimal jika terjadi kegagalan gateway.
     */
    public int $tries = 3;

    /**
     * Waktu jeda antar percobaan (detik).
     */
    public array $backoff = [5, 15, 30];

    /**
     * Timeout eksekusi job (detik).
     */
    public int $timeout = 15;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Student $student,
        public Attendance $attendance
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        WhatsAppService::sendAttendanceNotification($this->student, $this->attendance);
    }
}

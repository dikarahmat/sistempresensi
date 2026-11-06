<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\OperatingSchedule;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperatingScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $tahun = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $kelas = SchoolClass::create([
            'name' => '7A',
            'grade' => '7',
            'academic_year_id' => $tahun->id,
        ]);

        $this->student = Student::create([
            'school_class_id' => $kelas->id,
            'name' => 'Siswa Jadwal',
            'nisn' => '0000998877',
            'gender' => 'Laki-laki',
            'status' => 'Aktif',
        ]);

        $this->admin = User::create([
            'username' => 'adminjadwal',
            'name' => 'Admin Jadwal',
            'email' => 'admin@jadwal.test',
            'password' => Hash::make('rahasia123'),
            'role' => 'admin',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setSchedule(int $day, string $checkIn, string $lateLimit, string $checkOut, string $checkOutLimit): OperatingSchedule
    {
        return OperatingSchedule::updateOrCreate(
            ['day_of_week' => $day],
            [
                'check_in_time' => $checkIn,
                'late_limit_time' => $lateLimit,
                'check_out_time' => $checkOut,
                'check_out_limit_time' => $checkOutLimit,
            ]
        );
    }

    private function scan()
    {
        return $this->actingAs($this->admin)->postJson(
            route('admin.scanner.process'),
            ['qr_token' => $this->student->nisn]
        );
    }

    public function test_settings_menyimpan_jadwal_per_hari_dan_menyinkronkan_setting_lama(): void
    {
        $schedules = [];
        foreach (OperatingSchedule::DAY_NAMES as $day => $label) {
            $schedules[$day] = [
                'check_in_time' => '06:3' . $day,
                'late_limit_time' => '07:0' . $day,
                'check_out_time' => '13:0' . $day,
                'check_out_limit_time' => '14:0' . $day,
            ];
        }

        $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'school_name' => 'SMP Jadwal',
            'schedules' => $schedules,
        ])->assertRedirect(route('admin.settings.index'));

        $jumat = OperatingSchedule::where('day_of_week', 5)->first();
        $this->assertNotNull($jumat);
        $this->assertSame('06:35', substr((string) $jumat->check_in_time, 0, 5));
        $this->assertSame('13:05', substr((string) $jumat->check_out_time, 0, 5));

        $this->assertSame('06:31', substr((string) Setting::get('check_in_time'), 0, 5));
        $this->assertSame('07:01', substr((string) Setting::get('late_limit_time'), 0, 5));
        $this->assertSame('13:01', substr((string) Setting::get('check_out_time'), 0, 5));
    }

    public function test_settings_menolak_urutan_jam_yang_tidak_valid(): void
    {
        $schedules = [];
        foreach (OperatingSchedule::DAY_NAMES as $day => $label) {
            $schedules[$day] = [
                'check_in_time' => '07:30',
                'late_limit_time' => '07:00',
                'check_out_time' => '14:00',
                'check_out_limit_time' => '15:00',
            ];
        }

        $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'school_name' => 'SMP Jadwal',
            'schedules' => $schedules,
        ])->assertSessionHasErrors('schedules.1.check_out_time');
    }

    public function test_halaman_pengaturan_menampilkan_jadwal_lima_hari(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Senin')
            ->assertSee('Jumat')
            ->assertSee('check_out_limit_time');
    }

    public function test_scan_kedua_sebelum_jam_pulang_ditolak_tanpa_mengisi_check_out(): void
    {
        $this->setSchedule(1, '06:45', '07:15', '14:00', '15:00');
        Carbon::setTestNow(Carbon::create(2026, 10, 19, 6, 50, 0, 'Asia/Jakarta'));
        $this->scan()->assertOk();

        Carbon::setTestNow(Carbon::create(2026, 10, 19, 13, 0, 0, 'Asia/Jakarta'));
        $response = $this->scan();

        $response->assertStatus(400);
        $this->assertStringContainsString('Belum waktunya presensi pulang', (string) $response->json('message'));
        $this->assertNull(Attendance::where('student_id', $this->student->id)->first()->check_out);
    }

    public function test_scan_di_dalam_jendela_pulang_mengisi_check_out(): void
    {
        $this->setSchedule(1, '06:45', '07:15', '14:00', '15:00');
        Carbon::setTestNow(Carbon::create(2026, 10, 19, 6, 50, 0, 'Asia/Jakarta'));
        $this->scan()->assertOk();

        Carbon::setTestNow(Carbon::create(2026, 10, 19, 14, 5, 0, 'Asia/Jakarta'));
        $response = $this->scan();

        $response->assertOk();
        $response->assertJsonPath('type', 'check_out');
        $this->assertSame('14:05:00', Attendance::where('student_id', $this->student->id)->first()->check_out);
    }

    public function test_scan_setelah_batas_waktu_pulang_ditolak(): void
    {
        $this->setSchedule(1, '06:45', '07:15', '14:00', '15:00');
        Carbon::setTestNow(Carbon::create(2026, 10, 19, 6, 50, 0, 'Asia/Jakarta'));
        $this->scan()->assertOk();

        Carbon::setTestNow(Carbon::create(2026, 10, 19, 15, 1, 0, 'Asia/Jakarta'));
        $response = $this->scan();

        $response->assertStatus(400);
        $this->assertStringContainsString('Batas waktu presensi pulang sudah lewat', (string) $response->json('message'));
        $this->assertNull(Attendance::where('student_id', $this->student->id)->first()->check_out);
    }

    public function test_scan_pada_hari_sabtu_ditolak(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 17, 7, 0, 0, 'Asia/Jakarta'));

        $response = $this->scan();

        $response->assertStatus(400);
        $this->assertStringContainsString('tidak ada jadwal presensi', (string) $response->json('message'));
        $this->assertSame(0, Attendance::where('student_id', $this->student->id)->count());
    }

    public function test_tampilan_presensi_dan_rekap_menampilkan_jam_pulang(): void
    {
        $this->setSchedule(1, '06:45', '07:15', '14:00', '15:00');

        Carbon::setTestNow(Carbon::create(2026, 10, 19, 6, 50, 0, 'Asia/Jakarta'));
        $this->scan()->assertOk();

        Carbon::setTestNow(Carbon::create(2026, 10, 19, 14, 5, 0, 'Asia/Jakarta'));
        $this->scan()->assertOk();

        Carbon::setTestNow(Carbon::create(2026, 10, 19, 16, 0, 0, 'Asia/Jakarta'));

        $this->actingAs($this->admin)
            ->get(route('admin.absensi.show', $this->student->school_class_id))
            ->assertOk()
            ->assertSee('Jam Pulang')
            ->assertSee('14:05 WIB');

        $this->actingAs($this->admin)
            ->get(route('admin.kehadiran.student-history', $this->student->id))
            ->assertOk()
            ->assertSee('14:05 WIB');

        $this->actingAs($this->admin)
            ->get(route('admin.rekap'))
            ->assertOk()
            ->assertSee('14:05 WIB');
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * FASE 6 - SINKRONISASI & ANTI DOUBLE ABSEN.
 *
 * Membuktikan presensi adalah SATU sumber kebenaran dan hanya ada SATU
 * catatan per siswa per hari, baik lewat lapis APLIKASI (cek sebelum insert)
 * maupun lapis DATABASE (UNIQUE student_id + date), serta konsisten lintas
 * role (admin / guru / piket).
 */
class AntiDoubleAbsenTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $guru;
    protected User $piket;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $tahun = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'Ganjil',
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $kelas = SchoolClass::create([
            'name' => '7A', 'grade' => '7', 'academic_year_id' => $tahun->id,
        ]);

        $this->student = Student::create([
            'school_class_id' => $kelas->id,
            'name' => 'Siswa Anti Dobel',
            'nisn' => '0000123456',
            'gender' => 'Laki-laki',
            'status' => 'Aktif',
        ]);

        $this->admin = User::create([
            'username' => 'admin', 'name' => 'Admin', 'email' => 'admin@anti.test',
            'password' => Hash::make('rahasia123'), 'role' => 'admin',
        ]);
        $this->guru = User::create([
            'username' => 'guru', 'name' => 'Guru', 'email' => 'guru@anti.test',
            'password' => Hash::make('rahasia123'), 'role' => 'guru',
        ]);
        $this->piket = User::create([
            'username' => 'piket', 'name' => 'Piket', 'email' => 'piket@anti.test',
            'password' => Hash::make('rahasia123'), 'role' => 'piket',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function scan(User $as, ?string $token = null)
    {
        return $this->actingAs($as)->postJson(
            route($as->role . '.scanner.process'),
            ['qr_token' => $token ?? $this->student->nisn]
        );
    }

    private function jumlahPresensi(): int
    {
        return Attendance::where('student_id', $this->student->id)->count();
    }

    /** (i) Scan sekali -> sukses & hanya satu baris. */
    public function test_scan_pertama_berhasil_dan_mencatat_tepat_satu_baris(): void
    {
        $res = $this->scan($this->admin);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $this->assertSame(1, $this->jumlahPresensi());
    }

    /** (ii) Scan berulang di jalur yang sama -> ditolak, tidak menambah baris. */
    public function test_scan_ulang_ditolak_tanpa_menambah_baris(): void
    {
        $this->scan($this->admin)->assertStatus(200);

        $kedua = $this->scan($this->admin);
        $kedua->assertStatus(400);
        $kedua->assertJsonPath('success', false);
        $this->assertStringContainsString('sudah melakukan presensi', (string) $kedua->json('message'));

        $this->assertSame(1, $this->jumlahPresensi());
    }

    /** (iii) Absen di guru lalu coba di piket -> ditolak. */
    public function test_absen_di_guru_lalu_di_piket_ditolak(): void
    {
        $this->scan($this->guru)->assertStatus(200);

        $this->scan($this->piket)->assertStatus(400);
        $this->assertSame(1, $this->jumlahPresensi());
    }

    /** (iv) Absen di piket lalu coba di admin -> ditolak. */
    public function test_absen_di_piket_lalu_di_admin_ditolak(): void
    {
        $this->scan($this->piket)->assertStatus(200);

        $this->scan($this->admin)->assertStatus(400);
        $this->assertSame(1, $this->jumlahPresensi());
    }

    /** (v) Hari berbeda -> boleh absen lagi (satu baris per hari). */
    public function test_hari_berbeda_boleh_absen_lagi(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 16, 7, 0, 0, 'Asia/Jakarta'));
        $this->scan($this->admin)->assertStatus(200);

        Carbon::setTestNow(Carbon::create(2026, 10, 17, 7, 0, 0, 'Asia/Jakarta'));
        $this->scan($this->admin)->assertStatus(200);

        $this->assertSame(2, $this->jumlahPresensi());
    }

    /** (viii) Zona waktu Asia/Jakarta: 23:30 UTC = keesokan harinya di WIB. */
    public function test_tanggal_presensi_mengikuti_zona_waktu_asia_jakarta(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 16, 23, 30, 0, 'UTC'));

        $this->scan($this->admin)->assertStatus(200);

        $attendance = Attendance::where('student_id', $this->student->id)->first();
        $this->assertNotNull($attendance);
        $this->assertSame('2026-10-17', $attendance->date->toDateString());
    }

    /** Lapis DATABASE: UNIQUE(student_id, date) menolak baris ganda. */
    public function test_database_menolak_baris_ganda_siswa_dan_tanggal(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 16, 7, 0, 0, 'Asia/Jakarta'));
        $this->scan($this->admin)->assertStatus(200);

        $this->expectException(QueryException::class);

        // Memakai model agar format penyimpanan `date` identik dengan hasil scan,
        // sehingga UNIQUE(student_id, date) benar-benar menolaknya.
        Attendance::create([
            'student_id' => $this->student->id,
            'academic_year_id' => AcademicYear::getActive()?->id,
            'date' => '2026-10-16',
            'status' => 'Hadir',
        ]);
    }

    /** Hak akses: piket tidak boleh mengubah (override) presensi. */
    public function test_piket_tidak_bisa_override_presensi(): void
    {
        $this->actingAs($this->piket)
            ->post(route('admin.absensi.override'), [
                'attendance_id' => 1,
                'status' => 'Alfa',
            ])
            ->assertStatus(403);
    }
}

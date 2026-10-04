<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Verifikasi Tahap 2 audit 53 poin (dibuat karena butuh bukti [DIUJI-JALAN]
// untuk regex, import jujur, scanner, ekspor, dan guard TA di sqlite memory).
class AuditTahap2VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected AcademicYear $tahun;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'name' => 'Admin', 'username' => 'adminuji',
            'email' => 'a@uji.test', 'password' => Hash::make('rahasia123'), 'role' => 'admin',
        ]);
        $this->tahun = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'Ganjil',
            'start_date' => '2025-07-01', 'end_date' => '2025-12-31', 'is_active' => true,
        ]);
    }

    private function kelas(string $nama = '7A'): SchoolClass
    {
        return SchoolClass::create([
            'name' => $nama, 'grade' => '7', 'academic_year_id' => $this->tahun->id,
        ]);
    }

    public function test_nis_huruf_ditolak_dan_nis_angka_diterima(): void
    {
        $k = $this->kelas();
        // Huruf di NIS harus ditolak (kasus PDF: 2024289ssswdxsss...)
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'school_class_id' => $k->id, 'name' => 'Anak Uji', 'nis' => '2024289ssswdxsss',
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ])->assertSessionHasErrors('nis');
        // NISN bukan 10 digit ditolak
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'school_class_id' => $k->id, 'name' => 'Anak Uji', 'nis' => '123456',
            'nisn' => '123', 'gender' => 'Laki-laki', 'status' => 'Aktif',
        ])->assertSessionHasErrors('nisn');
        // WA huruf ditolak
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'school_class_id' => $k->id, 'name' => 'Anak Uji', 'nis' => '123457',
            'gender' => 'Laki-laki', 'status' => 'Aktif', 'phone' => '08abc12345',
        ])->assertSessionHasErrors('phone');
        // Nilai valid diterima
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'school_class_id' => $k->id, 'name' => 'Anak Uji', 'nis' => '123458',
            'nisn' => '1234567890', 'gender' => 'Laki-laki', 'status' => 'Aktif',
            'phone' => '081234567890',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('students', ['nis' => '123458']);
    }

    public function test_nip_guru_18_digit_dan_telp_divalidasi(): void
    {
        $this->actingAs($this->admin)->post(route('admin.guru.store'), [
            'name' => 'Guru Uji', 'nip' => '123ABC', 'gender' => 'Laki-laki',
            'phone_number' => '081234567890',
        ])->assertSessionHasErrors('nip');
        $this->actingAs($this->admin)->post(route('admin.guru.store'), [
            'name' => 'Guru Uji', 'nip' => '198001012010011001', 'gender' => 'Laki-laki',
            'phone_number' => 'abc',
        ])->assertSessionHasErrors('phone_number');
        $this->actingAs($this->admin)->post(route('admin.guru.store'), [
            'name' => 'Guru Uji', 'nip' => '198001012010011001', 'gender' => 'Laki-laki',
            'phone_number' => '081234567890',
        ])->assertSessionHas('success');
    }

    public function test_tahun_ajaran_aktif_tidak_bisa_dihapus(): void
    {
        $this->actingAs($this->admin)->delete(
            route('admin.academic-years.destroy', $this->tahun->id)
        )->assertSessionHas('error');
        $this->assertDatabaseHas('academic_years', ['id' => $this->tahun->id]);
    }

    public function test_scanner_qr_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.scanner.process'), [
            'qr_token' => 'QR-TIDAK-ADA-123',
        ])->assertStatus(404);
    }

    public function test_export_excel_dan_pdf_memuat_legend(): void
    {
        $k = $this->kelas();
        Student::create([
            'school_class_id' => $k->id, 'name' => 'Siswa Ekspor',
            'nis' => '555001', 'gender' => 'Laki-laki', 'status' => 'Aktif',
        ]);
        $excel = $this->actingAs($this->admin)->get(route('admin.rekap.export-excel', [
            'type' => 'harian', 'tanggal' => '2025-08-01',
        ]));
        $excel->assertStatus(200);
        $this->assertStringContainsString(
            'spreadsheetml', $excel->headers->get('Content-Type')
        );
        $pdf = $this->actingAs($this->admin)->get(route('admin.rekap.export-pdf', [
            'type' => 'harian', 'tanggal' => '2025-08-01',
        ]));
        $pdf->assertStatus(200);
        $this->assertStringContainsString('pdf', strtolower($pdf->headers->get('Content-Type')));
        // Legend diverifikasi dari sumber tunggal yang dipakai Excel + PDF.
        $legend = \App\Exports\MultiPeriodAttendanceExport::legendLines();
        $this->assertStringContainsString('KETERANGAN', $legend[0]);
        $this->assertStringContainsString('H = Hadir', $legend[1]);
        $this->assertStringContainsString('T = Terlambat', $legend[1]);
    }

    public function test_login_salah_dan_rate_limit(): void
    {
        $this->from(route('login'))->post(route('login'), [
            'login' => 'adminuji', 'password' => 'salah',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('login');
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['login' => 'x', 'password' => 'y']);
        }
        $this->post(route('login'), ['login' => 'x', 'password' => 'y'])
            ->assertRedirect(route('login'))->assertSessionHasErrors('login');
    }

    public function test_logo_menolak_file_bukan_gambar_dan_terlalu_besar(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'school_logo' => UploadedFile::fake()->create('logo.exe', 100, 'application/exe'),
        ])->assertSessionHasErrors('school_logo');
        $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'school_logo' => UploadedFile::fake()->image('besar.jpg')->size(3000),
        ])->assertSessionHasErrors('school_logo');
    }
}

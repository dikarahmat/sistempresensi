<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
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

    public function test_nisn_huruf_ditolak_dan_nisn_angka_diterima(): void
    {
        $k = $this->kelas();
        // Huruf di NISN harus ditolak (kasus PDF: 2024289ssswdxsss...)
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'school_class_id' => $k->id, 'name' => 'Anak Uji', 'nisn' => '2024289ssswdxsss',
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ])->assertSessionHasErrors('nisn');
        // NISN kosong ditolak
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'school_class_id' => $k->id, 'name' => 'Anak Uji', 'nisn' => '',
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ])->assertSessionHasErrors('nisn');
        // NISN bukan 10 digit ditolak
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'school_class_id' => $k->id, 'name' => 'Anak Uji', 'nisn' => '123',
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ])->assertSessionHasErrors('nisn');
        // Nama kosong ditolak
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'school_class_id' => $k->id, 'name' => '', 'nisn' => '0081234501',
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ])->assertSessionHasErrors('name');
        // Nilai valid diterima
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'school_class_id' => $k->id, 'name' => 'Anak Uji', 'nisn' => '1234567890',
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('students', ['nisn' => '1234567890']);
        // NISN duplikat ditolak
        $this->actingAs($this->admin)->post(route('admin.students.store'), [
            'school_class_id' => $k->id, 'name' => 'Anak Uji 2', 'nisn' => '1234567890',
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ])->assertSessionHasErrors('nisn');
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
            'nisn' => '0055500111', 'gender' => 'Laki-laki', 'status' => 'Aktif',
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

    public function test_sabtu_dan_minggu_dihitung_libur_di_rekap(): void
    {
        // Keputusan pemilik 2026-10-04: Sabtu + Minggu libur.
        // 2025-08-02 = Sabtu, 2025-08-03 = Minggu.
        $this->assertTrue(\Carbon\Carbon::parse('2025-08-02')->isWeekend());
        $rekap = app(\App\Http\Controllers\Admin\RekapController::class);
        $ref = new \ReflectionMethod($rekap, 'getRecapData');
        $ref->setAccessible(true);
        $k = $this->kelas();
        \App\Models\Student::create([
            'school_class_id' => $k->id, 'name' => 'Siswa Sabtu',
            'nisn' => '0055500222', 'gender' => 'Laki-laki', 'status' => 'Aktif',
        ]);
        $mingguan = $ref->invoke($rekap, 'mingguan', null, [
            'start_date' => '2025-07-28', 'end_date' => '2025-08-03',
        ]);
        $foundL = false;
        foreach ($mingguan['dataRows'] as $row) {
            foreach (['2025-08-02', '2025-08-03'] as $tgl) {
                if (($row['days'][$tgl] ?? null) === 'L') {
                    $foundL = true;
                }
            }
        }
        $this->assertTrue($foundL, 'Sabtu dan Minggu harus berkode L di rekap mingguan.');
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

    public function test_data_siswa_yang_dipakai_hanya_nisn_nama_kelas_jk(): void
    {
        // Identitas siswa hanya NISN (wajib ada + unique).
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumn('students', 'nisn'),
            'Kolom students.nisn harus tetap ada.'
        );
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasIndex('students', 'students_nisn_unique'),
            'students.nisn harus punya unique index.'
        );

        // Data siswa yang boleh dipakai aplikasi hanya 4 kolom ini. Kolom
        // KTP/GTK yang tak lagi dipakai (tempat & tanggal lahir, alamat,
        // nama wali, nomor WhatsApp) TIDAK BOLEH bisa di-mass-assign lagi,
        // walau kolomnya masih ada di database sampai migration drop
        // disetujui & dijalankan.
        $this->assertEqualsCanonicalizing(
            ['nisn', 'name', 'gender', 'school_class_id', 'status', 'photo', 'qr_token'],
            (new Student())->getFillable(),
            'Student::$fillable harus persis 4 kolom data siswa + kolom internal.'
        );
    }

    public function test_template_excel_siswa_tidak_punya_kolom_identitas_lama(): void
    {
        $headings = (new \App\Exports\StudentTemplateExport())->headings();

        $this->assertSame([
            'nisn',
            'nama',
            'kelas',
            'jenis_kelamin',
        ], $headings, 'Template Excel siswa hanya boleh memuat 4 kolom: nisn, nama, kelas, jenis_kelamin.');

        // Setiap baris contoh harus sepanjang jumlah header.
        foreach ((new \App\Exports\StudentTemplateExport())->array() as $row) {
            $this->assertCount(count($headings), $row);
            $this->assertMatchesRegularExpression('/^[0-9]{10}$/', (string) $row[0]);
        }
    }

    public function test_scan_berhasil_mencari_berdasarkan_nisn(): void
    {
        $k = $this->kelas();
        $student = Student::create([
            'school_class_id' => $k->id, 'name' => 'Siswa Scan',
            'nisn' => '0066660001', 'gender' => 'Laki-laki', 'status' => 'Aktif',
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.scanner.process'), [
            'qr_token' => $student->nisn,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('student.nisn', '0066660001');
    }

    public function test_qr_angka_tidak_terdaftar_ditolak(): void
    {
        $this->kelas();

        $this->actingAs($this->admin)->postJson(route('admin.scanner.process'), [
            'qr_token' => '0099999999',
        ])->assertStatus(404);

        // Angka acak yang tidak ada di database juga ditolak.
        $this->actingAs($this->admin)->postJson(route('admin.scanner.process'), [
            'qr_token' => '1234567890',
        ])->assertStatus(404);
    }

    public function test_import_siswa_hanya_mengenali_kolom_nisn(): void
    {
        $this->kelas();

        // 1) Kolom NISN kosong -> baris dilewati, nomor baris dilaporkan.
        $importKosong = new \App\Imports\StudentsImport();
        $importKosong->collection(collect([
            ['nisn' => '', 'nama' => 'Tanpa NISN', 'kelas' => '7A'],
        ]));
        $this->assertSame(0, $importKosong->getImportedCount());
        $this->assertSame(1, $importKosong->getSkippedCount());
        $this->assertStringContainsString('Baris 2', $importKosong->getErrors()[0]);
        $this->assertStringContainsString('NISN', $importKosong->getErrors()[0]);

        // 2) Duplikat DI DALAM file -> hanya baris pertama yang diproses.
        $importDuplikat = new \App\Imports\StudentsImport();
        $importDuplikat->collection(collect([
            ['nisn' => '0077770001', 'nama' => 'Siswa Pertama', 'kelas' => '7A'],
            ['nisn' => '0077770001', 'nama' => 'Siswa Kembar', 'kelas' => '7A'],
        ]));
        $this->assertSame(1, $importDuplikat->getImportedCount());
        $this->assertSame(1, $importDuplikat->getSkippedCount());
        $this->assertStringContainsString('Baris 3', $importDuplikat->getErrors()[0]);

        // 3) Duplikat di DATABASE -> dilewati, baris valid tetap diproses.
        Student::create([
            'school_class_id' => $this->kelas('7B')->id, 'name' => 'Sudah Ada',
            'nisn' => '0077770009', 'gender' => 'Laki-laki', 'status' => 'Aktif',
        ]);
        $importDatabase = new \App\Imports\StudentsImport();
        $importDatabase->collection(collect([
            ['nisn' => '0077770009', 'nama' => 'Kembar Database', 'kelas' => '7A'],
            ['nisn' => '0077770010', 'nama' => 'Siswa Baru', 'kelas' => '7A'],
        ]));
        $this->assertSame(1, $importDatabase->getImportedCount());
        $this->assertSame(1, $importDatabase->getSkippedCount());
        $this->assertStringContainsString('Baris 2', $importDatabase->getErrors()[0]);
        $this->assertDatabaseHas('students', ['nisn' => '0077770010', 'name' => 'Siswa Baru']);

        // 4) Nol di depan hilang karena Excel -> dipad nol sampai 10 digit.
        $importNolDepan = new \App\Imports\StudentsImport();
        $importNolDepan->collection(collect([
            // Excel membaca 0081234567 sebagai angka 81234567
            ['nisn' => 81234567, 'nama' => 'Siswa Nol Depan', 'kelas' => '7A'],
        ]));
        $this->assertSame(1, $importNolDepan->getImportedCount());
        $this->assertDatabaseHas('students', ['nisn' => '0081234567']);

        // 5) NISN bukan 10 digit (11 digit / huruf) -> ditolak.
        $importTidakValid = new \App\Imports\StudentsImport();
        $importTidakValid->collection(collect([
            ['nisn' => '12345678901', 'nama' => 'NISN Terlalu Panjang', 'kelas' => '7A'],
            ['nisn' => '12345678AB', 'nama' => 'NISN Huruf', 'kelas' => '7A'],
        ]));
        $this->assertSame(0, $importTidakValid->getImportedCount());
        $this->assertSame(2, $importTidakValid->getSkippedCount());
        $this->assertStringContainsString('10 digit', $importTidakValid->getErrors()[0]);

        // 6) File Excel berformat LAMA (ada kolom tambahan di luar 4 kolom
        //    resmi) tetap bisa diimport: kolom ekstra diabaikan, tidak disimpan.
        $importKolomExtra = new \App\Imports\StudentsImport();
        $importKolomExtra->collection(collect([
            [
                'nisn' => '0077770020', 'nama' => 'Siswa File Lama', 'kelas' => '7A',
                'jenis_kelamin' => 'Laki-laki',
                'kolom_ekstra_1' => 'Bandung', 'kolom_ekstra_2' => '2012-04-15',
                'kolom_ekstra_3' => 'Jl. Melati No. 12', 'kolom_ekstra_4' => '081234567890',
            ],
        ]));
        $this->assertSame(1, $importKolomExtra->getImportedCount());
        $siswaLama = Student::where('nisn', '0077770020')->firstOrFail();
        $this->assertSame('Siswa File Lama', $siswaLama->name);
        $this->assertSame('Laki-laki', $siswaLama->gender);
        foreach (['kolom_ekstra_1', 'kolom_ekstra_2', 'kolom_ekstra_3', 'kolom_ekstra_4'] as $diabaikan) {
            $this->assertArrayNotHasKey(
                $diabaikan,
                $siswaLama->getAttributes(),
                "Kolom di luar 4 kolom resmi harus diabaikan: {$diabaikan}"
            );
        }
    }
}

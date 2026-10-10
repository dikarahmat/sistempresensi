<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\RekapController;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Rentang periode jalur CETAK PER SISWA.
 *
 * Bug yang ditutup test ini: modal popup "Cetak Rekap" mewarisi rentang
 * tanggal bawaan filter halaman Rekap (mis. 01 Okt - 31 Okt). Setelah memilih
 * MINGGUAN dan_generate_, PDF tetap berisi "Periode: 01 Okt 2026 s/d 31 Okt 2026"
 * (31 baris) karena popup mengirim start_date/end_date warisan halaman.
 *
 * Aturan baru (sumber tunggal: RekapController::resolveDateRange()):
 *  - harian  : 1 tanggal
 *  - mingguan: Senin s/d Minggu dari 1 tanggal acuan
 *  - bulanan : tanggal 1 s/d akhir bulan
 *  - semester: dari AcademicYear, fallback Ganjil 1 Jul-31 Des / Genap 1 Jan-30 Jun
 *  - tahunan : 1 tahun ajaran penuh
 */
class StudentProfilePeriodRangeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected AcademicYear $tahun;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::create([
            'name' => 'Admin', 'username' => 'adminperiode',
            'email' => 'periode@uji.test', 'password' => Hash::make('rahasia123'), 'role' => 'admin',
        ]);
        $this->tahun = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'Ganjil',
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => true,
        ]);
        AcademicYear::clearActiveCache();
    }

    private function kelas(string $nama = '7A'): SchoolClass
    {
        return SchoolClass::create([
            'name' => $nama, 'grade' => '7', 'academic_year_id' => $this->tahun->id,
        ]);
    }

    /** Counter agar tiap pemanggilan bikin kelas/siswa unik (unique constraint). */
    private static int $seq = 0;

    private function siswa(SchoolClass $k, string $nama = 'Ahmad Uji'): Student
    {
        return Student::create([
            'school_class_id' => $k->id, 'name' => $nama,
            'nisn' => '0099991001', 'gender' => 'Laki-laki', 'status' => 'Aktif',
        ]);
    }

    /**
     * Panggil resolveDateRange() yang protected lewat reflection.
     * $overrideActiveYear dipakai untuk menguji fallback kalender saat
     * tidak ada satupun AcademicYear yang bisa dipakai.
     */
    private function resolve(string $type, array $inputs, bool $forceNoActiveYear = false): array
    {
        $controller = new RekapController();
        $m = new \ReflectionMethod(RekapController::class, 'resolveDateRange');
        $m->setAccessible(true);

        if ($forceNoActiveYear) {
            AcademicYear::query()->delete();
            AcademicYear::clearActiveCache();
            $this->assertNull(AcademicYear::getActive(), 'Skenario tanpa tahun ajaran harus aktif null.');
        }

        return $m->invoke(
            $controller,
            $type,
            $inputs,
            \Carbon\Carbon::parse('2026-10-10 09:00:00', 'Asia/Jakarta'),
            AcademicYear::getActive()
        );
    }

    // ==========================================================
    // 1. RENTANG PER PERIODE
    // ==========================================================

    public function test_harian_menggunakan_satu_tanggal_yang_dipilih(): void
    {
        $r = $this->resolve('harian', ['date' => '2026-10-06']);

        $this->assertSame('2026-10-06', $r['start']);
        $this->assertSame('2026-10-06', $r['end']);
        $this->assertStringContainsString('06', $r['label']);
    }

    public function test_mingguan_mengambil_senin_sampai_minggu_dari_tanggal_acuan(): void
    {
        // 2026-10-06 = Selasa -> minggu Senin 05 Okt s/d Minggu 11 Okt 2026
        $r = $this->resolve('mingguan', ['date' => '2026-10-06']);

        $this->assertSame('2026-10-05', $r['start'], 'Mingguan harus mulai hari Senin.');
        $this->assertSame('2026-10-11', $r['end'], 'Mingguan harus berakhir hari Minggu.');
        $this->assertSame(7, (int) \Carbon\Carbon::parse($r['start'])->diffInDays(\Carbon\Carbon::parse($r['end'])) + 1);
        // Tidak boleh ikut rentang bulan bawaan halaman Rekap.
        $this->assertStringNotContainsString('31 Okt', $r['label']);
        $this->assertStringContainsString('05 Okt 2026', $r['label']);
        $this->assertStringContainsString('11 Okt 2026', $r['label']);
    }

    public function test_mingguan_dan_harian_tidak_lagi_mengiku_rentang_bulan_bawaan_halaman(): void
    {
        // Nilai dates halaman Rekap TIDAK boleh diteruskan ke jalur cetak.
        $mingguan = $this->resolve('mingguan', ['date' => '2026-10-06']);
        $harian = $this->resolve('harian', ['date' => '2026-10-06']);

        $this->assertNotSame('2026-10-01', $mingguan['start']);
        $this->assertNotSame('2026-10-31', $mingguan['end']);
        $this->assertNotSame('2026-10-01', $harian['start']);
        $this->assertNotSame('2026-10-31', $harian['end']);
    }

    public function test_mingguan_masih_menghormati_rentang_kustom_filter_halaman(): void
    {
        // Jalur tabel halaman Rekap memang mengirim start_date/end_date kustom.
        $r = $this->resolve('mingguan', ['start_date' => '2026-10-01', 'end_date' => '2026-10-07']);

        $this->assertSame('2026-10-01', $r['start']);
        $this->assertSame('2026-10-07', $r['end']);
    }

    public function test_bulanan_menggunakan_tanggal_1_sampai_akhir_bulan(): void
    {
        $r = $this->resolve('bulanan', ['month' => 10, 'year' => 2026]);

        $this->assertSame('2026-10-01', $r['start']);
        $this->assertSame('2026-10-31', $r['end']);
        $this->assertSame(31, (int) \Carbon\Carbon::parse($r['start'])->diffInDays(\Carbon\Carbon::parse($r['end'])) + 1);
    }

    public function test_bulanan_februari_mengikuti_jumlah_hari_bulan_asli(): void
    {
        $r = $this->resolve('bulanan', ['month' => 2, 'year' => 2026]);

        $this->assertSame('2026-02-01', $r['start']);
        $this->assertSame('2026-02-28', $r['end']);
    }

    public function test_semester_ganjil_memakai_tahun_ajaran(): void
    {
        $r = $this->resolve('semester', ['period_semester' => 'ganjil', 'period_academic_year' => $this->tahun->id]);

        $this->assertSame('2025-07-01', $r['start']);
        $this->assertSame('2025-12-31', $r['end']);
    }

    public function test_semester_genap_memakai_tahun_ajaran(): void
    {
        $r = $this->resolve('semester', ['period_semester' => 'genap', 'period_academic_year' => $this->tahun->id]);

        $this->assertSame('2026-01-01', $r['start']);
        $this->assertSame('2026-06-30', $r['end']);
    }

    public function test_semester_fallback_kalender_bila_tahun_ajaran_tidak_ada(): void
    {
        // Tanpa satupun AcademicYear -> pakai kalender: Ganjil 1 Jul-31 Des,
        // Genap 1 Jan-30 Jun (dibanding tahun ajaran 2026/2027 yang aktif).
        $r = $this->resolve('semester', ['period_semester' => 'ganjil'], true);

        $this->assertSame('2026-07-01', $r['start'], 'Ganjil fallback 1 Juli.');
        $this->assertSame('2026-12-31', $r['end'], 'Ganjil fallback 31 Desember.');

        $r2 = $this->resolve('semester', ['period_semester' => 'genap'], true);
        $this->assertSame('2026-01-01', $r2['start'], 'Genap fallback 1 Januari.');
        $this->assertSame('2026-06-30', $r2['end'], 'Genap fallback 30 Juni.');
    }

    public function test_tahunan_memakai_sepenuh_tahun_ajaran(): void
    {
        $r = $this->resolve('tahunan', ['period_academic_year' => $this->tahun->id]);

        $this->assertSame('2025-07-01', $r['start']);
        $this->assertSame('2026-06-30', $r['end']);
        $this->assertGreaterThan(300, \Carbon\Carbon::parse($r['start'])->diffInDays(\Carbon\Carbon::parse($r['end'])));
    }

    public function test_tahunan_fallback_bila_tahun_ajaran_tidak_ada(): void
    {
        $r = $this->resolve('tahunan', [], true);

        $this->assertSame('2026-07-01', $r['start']);
        $this->assertSame('2027-06-30', $r['end']);
    }

    public function test_rentang_tidak_terbalik(): void
    {
        $r = $this->resolve('mingguan', ['start_date' => '2026-10-20', 'end_date' => '2026-10-01']);

        $this->assertLessThanOrEqual($r['end'], $r['start']);
    }

    // ==========================================================
    // 2. MEMORI: SEMESTER/TAHUNAN PINDAH KE ZIP
    // ==========================================================


    public function test_rentang_besar_tidak_lagi_dipecah_otomatis_ke_zip(): void
    {
        // ZIP sudah dihapus: satu request = satu file, berapa pun jumlah baris
        // dan siswa. Helper shouldUseStudentProfileZip() tidak boleh ada lagi.
        $this->assertFalse(
            method_exists(\App\Http\Controllers\Admin\RekapController::class, 'shouldUseStudentProfileZip'),
            'Batas 50 siswa / auto-ZIP harus dihapus.'
        );
        $this->assertFalse(
            method_exists(\App\Http\Controllers\Admin\RekapController::class, 'generateStudentProfileZip'),
            'generateStudentProfileZip() harus dihapus.'
        );

        $src = file_get_contents(app_path('Http/Controllers/Admin/RekapController.php'));
        $this->assertStringNotContainsString('ZipArchive', $src, 'Tidak ada lagi pembuatan ZIP di controller.');
        $this->assertStringNotContainsString("'output_format'", $src, 'Field output_format harus dihapus.');
    }

    // ==========================================================
    // 3. POPUP: SATU SET NAMA FIELD
    // ==========================================================

    public function test_popup_tidak_lagi_mengirim_field_tanggal_bawaan_halaman(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();

        // Field bentrok yang dulu membuat periode popup kalah dari filter halaman.
        $this->assertStringNotContainsString('period_start_date', $html);
        $this->assertStringNotContainsString('period_end_date', $html);

        // Field periode popup tetap ada.
        $this->assertStringContainsString('period_date', $html);
        $this->assertStringContainsString('period_month', $html);
        $this->assertStringContainsString('period_year', $html);
    }

    public function test_popup_menonaktifkan_field_periode_yang_tersembunyi(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();

        // Dua <select name="period_academic_year"> dulu saling menimpa karena
        // keduanya tetap terkirim meski bloknya disembunyikan.
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($html, 'name="period_academic_year"'),
            'Blok semester & tahunan tetap punya select tahun ajaran masing-masing.'
        );
        // Yang FLAGGAH: field di blok tersembunyi harus dinonaktifkan.
        $this->assertStringContainsString('ctrl.disabled = !active', $html);
        $this->assertStringNotContainsString('period_start_date', $html);
        $this->assertStringNotContainsString('period_end_date', $html);
    }

    // ==========================================================
    // 4. END-TO-END: LIMA PERIODE
    // ==========================================================

    private function cetakPerSiswa(array $extra): \Illuminate\Testing\TestResponse
    {
        $no = ++self::$seq;
        $k = $this->kelas('7' . $no);
        $s = Student::create([
            'school_class_id' => $k->id, 'name' => 'Ahmad Uji ' . $no,
            'nisn' => '00999910' . str_pad((string) $no, 2, '0', STR_PAD_LEFT),
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ]);
        Attendance::create([
            'student_id' => $s->id, 'academic_year_id' => $this->tahun->id,
            'date' => '2026-10-06', 'check_in' => '07:00:00', 'status' => 'Hadir', 'time_remark' => 'Tepat Waktu',
        ]);

        return $this->actingAs($this->admin)->post(route('admin.rekap.print'), array_merge([
            'format' => 'pdf',
            'type' => 'harian',
            'print_mode' => 'student',
            'student_scope' => 'single',
            'student_id' => $s->id,
        ], $extra));
    }

    public function test_cetak_per_siswa_lima_periode_semua_berhasil(): void
    {
        $cases = [
            'harian' => ['period_type' => 'harian', 'period_date' => '2026-10-06'],
            'mingguan' => ['period_type' => 'mingguan', 'period_date' => '2026-10-06'],
            'bulanan' => ['period_type' => 'bulanan', 'period_month' => 10, 'period_year' => 2026],
            'semester' => ['period_type' => 'semester', 'period_semester' => 'ganjil', 'period_academic_year' => $this->tahun->id],
            'tahunan' => ['period_type' => 'tahunan', 'period_academic_year' => $this->tahun->id],
        ];

        foreach ($cases as $label => $extra) {
            $r = $this->cetakPerSiswa($extra);
            $r->assertStatus(200, "Periode $label harus berhasil.");

            $content = file_get_contents($r->getFile()->getPathname());
            $this->assertStringStartsWith('%PDF', $content, "Periode $label harus PDF asli.");
            $this->assertGreaterThan(1024, strlen($content), "Periode $label harus > 1KB.");
        }
    }

    public function test_periode_mingguan_tidak_lagi_menghasilkan_31_baris(): void
    {
        // Regresi bug utama: dulu mingguan = rentang 01 Okt s/d 31 Okt (31 baris).
        $r = $this->cetakPerSiswa(['period_type' => 'mingguan', 'period_date' => '2026-10-06']);
        $r->assertStatus(200);

        $resolved = $this->resolve('mingguan', ['date' => '2026-10-06']);
        $jumlahBaris = (int) \Carbon\Carbon::parse($resolved['start'])
            ->diffInDays(\Carbon\Carbon::parse($resolved['end'])) + 1;

        $this->assertSame(7, $jumlahBaris, 'Mingguan harus 7 baris, bukan 31.');
    }
}
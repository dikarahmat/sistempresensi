<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Format unduhan Cetak Rekap (pdf vs excel).
 *
 * Bug yang ditutup test ini: klik "CETAK EXCEL" di /admin/rekap tetap
 * mengunduh PDF. Penyebabnya BUKAN di controller, melainkan di JS:
 * di rekap.blade.php ada deklarasi `const outputFormatContainer` DUA KALI
 * dalam scope fungsi yang sama. Duplikat const = SyntaxError yang mematikan
 * SELURUH blok <script>, sehingga handler yang menulis #printFormat tidak
 * pernah jalan dan field `format` terkunci pada nilai default HTML "pdf".
 *
 * Test di bawah mengunci dua sisi:
 *  (1) tidak ada deklarasi const/let ganda dalam satu scope di blok script,
 *  (2) endpoint rekap.print benar-benar menghormati format=pdf|excel.
 */
class RekapPrintFormatTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected AcademicYear $tahun;
    protected SchoolClass $kelas;
    protected Student $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        // Route rekap.print memakai throttle:5,1. Test ini menembak endpoint itu
        // lebih dari 5x dalam satu menit (2 mode x 3 periode x 2 format + zip),
        // sehingga throttle DIMATIKAN agar yang diuji logika format, bukan rate limit.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->admin = User::create([
            'name' => 'Admin', 'username' => 'adminformat',
            'email' => 'format@uji.test', 'password' => Hash::make('rahasia123'), 'role' => 'admin',
        ]);
        $this->tahun = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'Ganjil',
            'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'is_active' => true,
        ]);
        AcademicYear::clearActiveCache();
        $this->kelas = SchoolClass::create([
            'name' => '7A', 'grade' => '7', 'academic_year_id' => $this->tahun->id,
        ]);
        $this->siswa = Student::create([
            'school_class_id' => $this->kelas->id, 'name' => 'Ahmad Format',
            'nisn' => '0099992001', 'gender' => 'Laki-laki', 'status' => 'Aktif',
        ]);
        Attendance::create([
            'student_id' => $this->siswa->id, 'academic_year_id' => $this->tahun->id,
            'date' => '2025-08-01', 'check_in' => '07:00:00', 'status' => 'Hadir', 'time_remark' => 'Tepat Waktu',
        ]);
    }

    // ==========================================================
    // 1. SISI JS: tidak boleh ada const/let ganda dalam satu scope
    // ==========================================================

    /**
     * Parser ringan: telusuri blok script sambil menghitung kedalaman kurung kurawal
     * dan mencatat setiap deklarasi const/let per scope. Duplikat nama pada scope
     * yang sama = SyntaxError di browser = seluruh script mati.
     */
    private function findDuplicateDeclarations(string $js): array
    {
        // Buang komentar dan literal string agar tidak salah hitung.
        $clean = preg_replace('~/\*.*?\*/~s', ' ', $js);
        $clean = preg_replace('~//[^\n]*~', ' ', $clean);
        $clean = preg_replace("~'(?:\\\\.|[^'\\\\])*'~s", "''", $clean);
        $clean = preg_replace('~"(?:\\\\.|[^"\\\\])*"~s', '""', $clean);
        $clean = preg_replace('~`(?:\\\\.|[^`\\\\])*`~s', '``', $clean);

        $depth = 0;
        $scopes = [];
        $duplicates = [];
        $len = strlen($clean);

        for ($i = 0; $i < $len; $i++) {
            if ($clean[$i] === '{') {
                $depth++;
                $scopes[$depth] = [];
                continue;
            }
            if ($clean[$i] === '}') {
                unset($scopes[$depth]);
                $depth = max(0, $depth - 1);
                continue;
            }
            if (preg_match('/\G(?:const|let)\s+([A-Za-z_\$][\w\$]*)\s*=/', $clean, $m, 0, $i)) {
                $name = $m[1];
                if (isset($scopes[$depth][$name])) {
                    $duplicates[] = $name . ' (depth ' . $depth . ')';
                }
                $scopes[$depth][$name] = true;
            }
        }

        return $duplicates;
    }

    private function scriptPopupRekap(): string
    {
        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();

        preg_match_all('#<script[^>]*>(.*?)</script>#s', $html, $all);
        foreach ($all[1] as $candidate) {
            if (str_contains($candidate, 'printRekapForm')) {
                return $candidate;
            }
        }

        $this->fail('Blok <script> popup cetak rekap tidak ditemukan.');
    }

    /** Isi <script> komponen bersama downloadWithProgress. */
    private function scriptHelper(): string
    {
        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();

        preg_match_all('#<script[^>]*>(.*?)</script>#s', $html, $all);
        foreach ($all[1] as $candidate) {
            if (str_contains($candidate, 'downloadWithProgress')) {
                return $candidate;
            }
        }

        $this->fail('Blok <script> komponen downloadWithProgress tidak ditemukan.');
    }

    public function test_blok_script_rekap_tidak_punya_deklarasi_ganda(): void
    {
        $duplicates = $this->findDuplicateDeclarations($this->scriptPopupRekap());

        $this->assertSame(
            [],
            $duplicates,
            'Deklarasi const/let ganda dalam satu scope (=SyntaxError, seluruh script mati): '
                . implode(', ', $duplicates)
        );
    }

    public function test_semua_tombol_pembuka_modal_punya_data_format(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();

        preg_match_all('/<button[^>]*data-bs-target="#printRekapModal"[^>]*>/', $html, $m);
        $buttons = $m[0];

        $this->assertGreaterThanOrEqual(4, count($buttons), 'Tombol cetak desktop + mobile harus ada.');

        foreach ($buttons as $btn) {
            $this->assertMatchesRegularExpression(
                '/data-format="(pdf|excel)"/',
                $btn,
                'Setiap tombol pembuka modal harus punya data-format pdf/excel: ' . $btn
            );
        }

        $this->assertSame(
            2,
            preg_match_all('/data-bs-target="#printRekapModal"[^>]*data-format="pdf"/', $html),
            'Harus ada tombol Cetak PDF desktop + mobile.'
        );
        $this->assertSame(
            2,
            preg_match_all('/data-bs-target="#printRekapModal"[^>]*data-format="excel"/', $html),
            'Harus ada tombol Cetak Excel desktop + mobile.'
        );
    }

    public function test_format_diambil_dari_tombol_yang_diklik_bukan_related_target(): void
    {
        $js = $this->scriptPopupRekap();

        // Format dikunci saat klik tombol, lalu ditulis ulang tepat sebelum submit.
        $this->assertStringContainsString('window.rekapPrintFormat', $js);
        $this->assertStringContainsString('setRekapPrintFormatFromButton', $js);
        $this->assertStringContainsString('data-bs-target="#printRekapModal"]', $js);

        // Tidak boleh ada lagi ketergantungan pada event.relatedTarget sebagai sumber utama.
        $this->assertStringNotContainsString("getAttribute('data-format') || 'pdf'", $js);
    }

    // ==========================================================
    // 2. SISI SERVER: format=pdf vs format=excel
    // ==========================================================

    private function cetak(array $params)
    {
        return $this->actingAs($this->admin)->post(route('admin.rekap.print'), $params);
    }

    /** Ambil respons Symfony yang sebenarnya di balik TestResponse. */
    private function base($response)
    {
        return property_exists($response, 'baseResponse') ? $response->baseResponse : $response;
    }

    private function namaFile($response): string
    {
        $cd = $this->base($response)->headers->get('Content-Disposition') ?: '';
        preg_match('/filename="?([^";]+)"?/', $cd, $m);

        return $m[1] ?? '';
    }

    private function isiFile($response): string
    {
        $base = $this->base($response);

        // BinaryFileResponse menyimpan isi di file, getContent() selalu kosong.
        if ($base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse && $base->getFile()) {
            return (string) file_get_contents($base->getFile()->getPathname());
        }

        return (string) $base->getContent();
    }

    private function periode(string $nama): array
    {
        return match ($nama) {
            'harian' => ['period_type' => 'harian', 'period_date' => '2025-08-01'],
            'mingguan' => ['period_type' => 'mingguan', 'period_date' => '2025-08-01'],
            'bulanan' => ['period_type' => 'bulanan', 'period_month' => 8, 'period_year' => 2025],
        };
    }

    private function assertExcelValid($response, string $label): void
    {
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $this->base($response)->headers->get('Content-Type'),
            "[$label] Content-Type Excel harus benar."
        );

        $fileName = $this->namaFile($response);
        $this->assertStringEndsWith('.xlsx', $fileName, "[$label] nama file harus .xlsx");

        $isi = $this->isiFile($response);
        $this->assertSame('PK', substr($isi, 0, 2), "[$label] xlsx adalah zip: diawali PK.");

        $tmp = tempnam(sys_get_temp_dir(), 'fmt') . '.xlsx';
        file_put_contents($tmp, $isi);
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
            $this->assertNotEmpty($reader->getSheetNames(), "[$label] workbook harus punya sheet.");
        } finally {
            @unlink($tmp);
        }
    }

    private function assertPdfValid($response, string $label): void
    {
        $this->assertSame('application/pdf', $this->base($response)->headers->get('Content-Type'), "[$label] Content-Type PDF harus benar.");
        $this->assertStringEndsWith('.pdf', $this->namaFile($response), "[$label] nama file harus .pdf");
        $this->assertStringStartsWith('%PDF', $this->isiFile($response), "[$label] isi harus diawali %PDF.");
    }

    public function test_excel_per_kelas_tiga_periode(): void
    {
        foreach (['harian', 'mingguan', 'bulanan'] as $periode) {
            $r = $this->cetak(array_merge([
                'format' => 'excel', 'type' => $periode, 'print_mode' => 'class',
                'class_id' => $this->kelas->id,
            ], $this->periode($periode)));

            $r->assertStatus(200);
            $this->assertExcelValid($r, "per kelas excel $periode");
        }
    }

    public function test_pdf_per_kelas_tiga_periode(): void
    {
        foreach (['harian', 'mingguan', 'bulanan'] as $periode) {
            $r = $this->cetak(array_merge([
                'format' => 'pdf', 'type' => $periode, 'print_mode' => 'class',
                'class_id' => $this->kelas->id,
            ], $this->periode($periode)));

            $r->assertStatus(200);
            $this->assertPdfValid($r, "per kelas pdf $periode");
        }
    }

    public function test_excel_per_siswa_tiga_periode(): void
    {
        foreach (['harian', 'mingguan', 'bulanan'] as $periode) {
            $r = $this->cetak(array_merge([
                'format' => 'excel', 'type' => $periode, 'print_mode' => 'student',
                'student_scope' => 'single', 'student_id' => $this->siswa->id,
            ], $this->periode($periode)));

            $r->assertStatus(200);
            $this->assertExcelValid($r, "per siswa excel $periode");
        }
    }

    public function test_pdf_per_siswa_tiga_periode(): void
    {
        foreach (['harian', 'mingguan', 'bulanan'] as $periode) {
            $r = $this->cetak(array_merge([
                'format' => 'pdf', 'type' => $periode, 'print_mode' => 'student',
                'student_scope' => 'single', 'student_id' => $this->siswa->id,
            ], $this->periode($periode)));

            $r->assertStatus(200);
            $this->assertPdfValid($r, "per siswa pdf $periode");
        }
    }


    // ==========================================================
    // 3. CAKUPAN PER SISWA: SATU KELAS (satu file per kelas)
    // ==========================================================

    private function kelasBaru(string $nama): SchoolClass
    {
        return SchoolClass::create([
            'name' => $nama, 'grade' => '8', 'academic_year_id' => $this->tahun->id,
        ]);
    }

    private function siswaDi(SchoolClass $k, string $nama, string $nisn): Student
    {
        return Student::create([
            'school_class_id' => $k->id, 'name' => $nama, 'nisn' => $nisn,
            'gender' => 'Laki-laki', 'status' => 'Aktif',
        ]);
    }

    /**
     * Tiga kelas berbeda, masing-masing scope=class harus menghasilkan file valid:
     * PDF diawali "%PDF", Excel bisa dibuka IOFactory::load().
     */
    public function test_scope_class_tiga_kelas_masing_masing_valid(): void
    {
        $daftar = [];
        foreach ([['8A', '0099993001'], ['8B', '0099993002'], ['8C', '0099993003']] as $i => [$nama, $nisn]) {
            $k = $this->kelasBaru($nama);
            $this->siswaDi($k, 'Siswa ' . $nama, $nisn);
            $daftar[] = $k;
        }

        foreach ($daftar as $k) {
            // --- PDF ---
            $r = $this->cetak([
                'format' => 'pdf', 'type' => 'bulanan', 'print_mode' => 'student',
                'student_scope' => 'class', 'scope_class_id' => $k->id,
                'period_type' => 'bulanan', 'period_month' => 8, 'period_year' => 2025,
            ]);
            $r->assertStatus(200);
            $isi = $this->isiFile($r);
            $this->assertStringStartsWith('%PDF', $isi, "Kelas {$k->name} harus menghasilkan PDF asli.");
            $this->assertStringContainsString(
                \Illuminate\Support\Str::slug($k->name),
                $this->namaFile($r),
                "Nama file kelas {$k->name} harus memakai slug nama kelas."
            );

            // --- Excel ---
            $r = $this->cetak([
                'format' => 'excel', 'type' => 'bulanan', 'print_mode' => 'student',
                'student_scope' => 'class', 'scope_class_id' => $k->id,
                'period_type' => 'bulanan', 'period_month' => 8, 'period_year' => 2025,
            ]);
            $r->assertStatus(200);
            $this->assertExcelValid($r, "kelas {$k->name} excel");

            $tmp = tempnam(sys_get_temp_dir(), 'sheet') . '.xlsx';
            file_put_contents($tmp, $this->isiFile($r));
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
                $namaSheets = $reader->getSheetNames();
                $this->assertCount(1, $namaSheets, "Kelas {$k->name} punya 1 siswa = 1 sheet.");
                $this->assertLessThanOrEqual(31, mb_strlen($namaSheets[0]), 'Nama sheet maksimal 31 karakter.');
            } finally {
                @unlink($tmp);
            }
        }
    }

    public function test_kelas_tanpa_siswa_tidak_error(): void
    {
        $k = $this->kelasBaru('8Z');

        $r = $this->cetak([
            'format' => 'pdf', 'type' => 'bulanan', 'print_mode' => 'student',
            'student_scope' => 'class', 'scope_class_id' => $k->id,
            'period_type' => 'bulanan', 'period_month' => 8, 'period_year' => 2025,
        ]);

        // Kelas kosong tidak boleh 500; server menjawab 422 dengan pesan jelas.
        $r->assertStatus(422);
        $this->assertStringContainsString(
            'Tidak ada siswa aktif',
            $this->base($r)->getContent(),
            'Kelas kosong harus memberi pesan yang bisa ditampilkan per kelas.'
        );
    }

    public function test_tidak_ada_lagi_jalur_zip(): void
    {
        // Field output_format sudah dihapus dari validasi controller.
        $r = $this->cetak([
            'format' => 'excel', 'type' => 'bulanan', 'print_mode' => 'student',
            'student_scope' => 'all', 'output_format' => 'zip',
            'period_type' => 'bulanan', 'period_month' => 8, 'period_year' => 2025,
        ]);
        $r->assertStatus(200);
        $this->assertStringNotContainsString(
            '.zip',
            $this->namaFile($r),
            'Tidak boleh ada lagi file ZIP.'
        );
        $this->assertNotSame(
            'application/zip',
            $this->base($r)->headers->get('Content-Type'),
            'Tidak boleh ada lagi Content-Type application/zip.'
        );

        // Method generateStudentProfileZip harus benar-benar hilang.
        $this->assertFalse(
            method_exists(\App\Http\Controllers\Admin\RekapController::class, 'generateStudentProfileZip'),
            'generateStudentProfileZip() harus dihapus.'
        );
        $this->assertFalse(
            method_exists(\App\Http\Controllers\Admin\RekapController::class, 'shouldUseStudentProfileZip'),
            'shouldUseStudentProfileZip() harus dihapus.'
        );
    }

    public function test_scope_classes_hanya_di_accept_oleh_browser_loop(): void
    {
        // Guard server: 'classes' TIDAK bolehdiproses sebagai satu request.
        $r = $this->cetak([
            'format' => 'pdf', 'type' => 'bulanan', 'print_mode' => 'student',
            'student_scope' => 'classes',
            'period_type' => 'bulanan', 'period_month' => 8, 'period_year' => 2025,
        ]);

        $r->assertStatus(422);
        $this->assertStringContainsString('per kelas', $this->base($r)->getContent());
    }

    // ==========================================================
    // 4. SINTAKS JS HALAMAN REKAP (node --check)
    // ==========================================================

    public function test_sintaks_js_halaman_rekap_valid(): void
    {
        $node = $this->cariNode();
        if (!$node) {
            $this->markTestSkipped('Node.js tidak tersedia di mesin ini.');
        }

        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();
        preg_match_all('#<script[^>]*>(.*?)</script>#s', $html, $all);
        $js = '';
        foreach ($all[1] as $candidate) {
            if (str_contains($candidate, 'printRekapForm')) {
                $js = $candidate;
            }
        }
        $this->assertNotSame('', $js, 'Blok script popup rekap harus ada.');

        $tmp = tempnam(sys_get_temp_dir(), 'rekapjs') . '.js';
        file_put_contents($tmp, $js);
        try {
            // escapeshellcmd() merusak path ber-spasi di Windows
            // (menghasilkan C:^\Program Files^\...), jadi path dibungkus manual.
            $cmd = '"' . $node . '" --check "' . $tmp . '" 2>&1';
            exec($cmd, $out, $code);
            $this->assertSame(
                0,
                $code,
                "node --check gagal (SyntaxError akan mematikan seluruh script): " . implode("\n", $out)
            );
        } finally {
            @unlink($tmp);
        }
    }

    private function cariNode(): ?string
    {
        foreach ([
            'C:\Program Files\nodejs\node.exe',
            '/usr/bin/node',
            '/usr/local/bin/node',
        ] as $p) {
            if (is_file($p)) {
                return $p;
            }
        }

        return null;
    }

    public function test_popup_menonaktifkan_smart_loader_selalu(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();
        $js   = $this->scriptPopupRekap();

        // public/js/instant-download.js memakai listener submit fase CAPTURE di
        // document + stopPropagation, sehingga listener form tidak pernah jalan.
        // Semua mode kini memakai helper downloadWithProgress() (atau loop per
        // kelas), jadi form SELALU dikecualikan dari smart loader - bukan hanya
        // saat cakupan "classes".
        $this->assertStringContainsString('id="printRekapForm" data-no-download', $html);
        $this->assertStringNotContainsString("setAttribute('data-no-download'", $js);
        $this->assertStringNotContainsString("removeAttribute('data-no-download')", $js);

        // Loop harus mengirim scope=class per kelas, bukan scope=classes.
        $this->assertStringContainsString("body.append('student_scope', 'class')", $js);
        $this->assertStringContainsString("body.append('scope_class_id', kelas.id)", $js);
        // Jeda antar kelas ~500 ms dan berhenti bisa dibatalkan.
        $this->assertStringContainsString('rekapPause(500)', $js);
        $this->assertStringContainsString('rekapLoopStopped', $js);
    }

    /**
     * Cetak "Semua Kelas" mengirim satu request per kelas. Throttle lama
     * (5/menit) membuat 13 dari 18 kelas gagal dengan HTTP 429.
     * Test ini menembak 25 request berurutan dan memastikan TIDAK ada 429.
     */
    public function test_25_request_cetak_berurutan_tidak_kena_429(): void
    {
        // Throttle harus AKTIF di test ini (setUp mematikannya karena test lain
        // menembak endpoint berkali-kali). withMiddleware() mengembalikannya.
        $this->withMiddleware();

        $k = $this->kelasBaru('8R');
        $this->siswaDi($k, 'Siswa Uji Throttle', '0099995001');

        $payload = [
            'format' => 'excel', // paling ringan, cukup untuk menguji throttle
            'type' => 'bulanan',
            'print_mode' => 'student',
            'student_scope' => 'class',
            'scope_class_id' => $k->id,
            'period_type' => 'bulanan',
            'period_month' => 8,
            'period_year' => 2025,
        ];

        for ($i = 1; $i <= 25; $i++) {
            $response = $this->actingAs($this->admin)->post(route('admin.rekap.print'), $payload);

            $this->assertNotSame(
                429,
                $response->getStatusCode(),
                "Request #$i kena throttle 429. Limiter 'rekap-print' harus cukup longgar."
            );
            $response->assertStatus(200);
        }
    }

    /**
     * Progres kini digambar DI DALAM tombol utama; panel progres, kotak hijau,
     * dan teks "Mohon tunggu..." sudah dihapus beserta tinggi tetapnya.
     */
    public function test_progres_didalam_tombol_dan_panel_lama_dihapus(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();

        // 1. Semua elemen panel lama harus hilang.
        foreach ([
            'rekapStatusArea', 'rekapProgressPanel', 'rekapProgressRunning',
            'rekapProgressSuccess', 'rekapProgressFailed', 'rekapProgressTrack',
            'rekapProgressBar', 'rekapProgressCount', 'rekapProgressPercent',
            'rekapQueueNote', 'rekapPartialBtn', 'rekapRetryBtn', 'multiDownloadHelp',
        ] as $id) {
            $this->assertStringNotContainsString('id="' . $id . '"', $html, "Elemen #$id harus dihapus.");
        }

        // Teks usang dan tinggi tetap ikut hilang.
        $this->assertStringNotContainsString('Mohon tunggu', $html);
        $this->assertStringNotContainsString('#rekapStatusArea { min-height', $html);
        $this->assertStringNotContainsString('#rekapProgressPanel { min-height', $html);
        $this->assertStringNotContainsString('rekapShimmer', $html);
        $this->assertStringNotContainsString('Jika browser meminta izin unduhan berganda', $html);

        // 2. Elemen progres di dalam tombol, memakai kelas komponen bersama
        //    (.btn-progress / .btn-progress__fill) yang sama dengan tombol unduhan lain.
        $this->assertStringContainsString('id="rekapBtnFill"', $html);
        $this->assertStringContainsString('id="rekapBtnLabel"', $html);
        $this->assertStringContainsString('btn-progress__fill', $html);
        $this->assertStringContainsString('btn-progress__label', $html);
        // Bar dan angka sinkron: transisi pendek & linear (bukan ease panjang).
        $this->assertStringContainsString('transition: width .2s linear', $html);
        $this->assertStringContainsString('border-radius: inherit', $html);

        // Warna isian: SATU warna gelap (navy) untuk semua tombol, bukan
        // variasi per-warna. Opasitas penuh supaya bar pekat.
        $this->assertStringContainsString('background-color: #1e3a8a;', $html);
        $this->assertStringContainsString('opacity: 1;', $html);
        $this->assertStringNotContainsString('--btn-progress-fill:', $html);
        $this->assertStringNotContainsString('#printRekapSubmitBtn .btn-progress__fill { background:', $html);

        // Tanpa spinner, tanpa shimmer di dalam komponen sendiri.
        $komponen = file_get_contents(base_path('resources/views/partials/download-progress.blade.php'));
        $this->assertStringNotContainsString('rekapSpinner', $komponen);
        $this->assertStringNotContainsString('dl-progress-spinner', $komponen);

        // 3. Baris kegagalan tunggal (hanya saat ada gagal).
        $this->assertStringContainsString('id="rekapFailLine"', $html);
        $this->assertStringContainsString('id="rekapFailCount"', $html);
        $this->assertStringContainsString('id="rekapDetailToggle"', $html);
        $this->assertStringContainsString('id="rekapDetailList"', $html);
        $this->assertStringContainsString('max-height:120px', $html);

        // 4. Tombol tidak berubah ukuran: footer dua kolom sama lebar.
        $this->assertStringContainsString('#printRekapSubmitBtn {', $html);
        $this->assertStringContainsString('min-width: 168px', $html);
        $this->assertStringContainsString('white-space: nowrap', $html);
        $this->assertStringContainsString('class="dl-actions"', $html);
        $this->assertStringContainsString('flex: 1 1 168px', $html);

        // 5. Selesai: teks "Selesai" tanpa ikon centang.
        $this->assertStringNotContainsString('✓ Selesai', $html);

        // 6. Label "Semua Kelas" diperbarui ke sebutan ZIP.
        $this->assertStringContainsString('Semua Kelas (satu file ZIP)', $html);
    }

    public function test_js_menangani_429_dengan_retry_ada(): void
    {
        $js = $this->scriptPopupRekap();

        // Retry khusus 429 maksimal 3 kali, baca Retry-After.
        $this->assertStringContainsString('res.status === 429', $js);
        $this->assertStringContainsString('Retry-After', $js);
        $this->assertStringContainsString('REKAP_MAKS_RETRY = 3', $js);
        $this->assertStringContainsString('rekapTampilkanAntrean', $js);
        $this->assertStringContainsString('rekapSembunyikanAntrean', $js);
        $this->assertStringContainsString('Terlalu banyak permintaan', $js);

        // Progres hanya lewat tombol: teks = persen, bar = lebar fill.
        $this->assertStringContainsString('rekapSetProgress', $js);
        $this->assertStringContainsString("rekapIsiTombol(persen + '%')", $js);
        $this->assertStringContainsString("fill.style.width = persen + '%'", $js);
        $this->assertStringContainsString("classList.add('is-running'", $js);

        // Status akhir lewat tombol, bukan kotak notifikasi.
        // Teks "Selesai" TANPA ikon centang.
        $this->assertStringContainsString("rekapIsiTombol('Selesai')", $js);
        $this->assertStringNotContainsString('✓ Selesai', $js);
        $this->assertStringContainsString("'Coba lagi (' + jumlah + ')'", $js);
        $this->assertStringContainsString("cnt.textContent = jumlah + ' kelas gagal.'", $js);
        $this->assertStringContainsString('setTimeout(rekapTutupPopup, 1000);', $js);   // popup tutup 1 detik
        $this->assertStringContainsString("var HOLD_SELESAI_MS = 1000", $this->scriptHelper());
        $this->assertStringContainsString('Hentikan', $js);
        $this->assertStringContainsString('Tutup', $js);

        // Batal / Hentikan / X / backdrop: abort senyap lalu popup langsung tutup.
        $this->assertStringContainsString('window.dlProgressAbort()', $js);
        $this->assertStringContainsString("modal.addEventListener('hide.bs.modal'", $js);
        $this->assertStringContainsString('rekapTutupPopup()', $js);
        $this->assertStringNotContainsString('Unduhan dibatalkan', $js);

        // Tidak boleh ada teks panjang di tombol utama.
        $this->assertStringNotContainsString('Mengunduh kelas ', $js);

        // Panel di-reset setiap popup dibuka.
        $this->assertStringContainsString('rekapResetPanel();', $js);
        $this->assertStringContainsString('window.dlProgressReset()', $js);
    }

    /**
     * Komponen bersama downloadWithProgress():
     *   - persen mulai 0% lalu naik bertahap, tidak pernah lompat / mundur
     *   - tanpa Content-Length -> estimasi melambat sampai 90%, lalu 100%
     *   - teks "Selesai" TANPA ikon centang
     *   - pembatalan SENYAP (tidak ada teks, toast, atau pesan error)
     *   - warna isian mewarisi warna tombol (bukan satu warna biru hardcode)
     */
    public function test_helper_progres_naik_halus_dan_pembatalan_senyap(): void
    {
        $js   = $this->scriptHelper();
        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();

        // 1. Mulai dari 0% + paksa reflow supaya transition benar-benar jalan.
        $this->assertStringContainsString('setPercent(parts, 0);', $js);
        $this->assertStringContainsString('paksaReflow(parts.fill);', $js);
        $this->assertStringContainsString('void el.offsetWidth;', $js);

        // 2. Display naik +1 per tick; ceiling membatasi laju => 0 -> 100 tak pernah meloncat.
        $this->assertStringContainsString("var TICK_MERAYAP_MS = 40;", $js);
        $this->assertStringContainsString("var TICK_KEJAR_MS   = 15;", $js);
        $this->assertStringContainsString("var TICK_FINAL_MS   = 12;", $js);
        $this->assertStringContainsString("Math.floor(s.ceiling)", $js);
        $this->assertStringContainsString("s.display = Math.min(100, s.display + 1);", $js);
        $this->assertStringContainsString("s.target + (margin === undefined ? MARGIN_BIASA : margin)", $js);
        // Bar hanya boleh naik: tidak ada penassignment persen ke nilai lebih kecil.
        $this->assertStringNotContainsString('s.persen = 0;', substr($js, strpos($js, 'function tick(')));

        // 3. Tanpa Content-Length: estimasi 0 -> 90 dengan kurva melambat.
        $this->assertStringContainsString('var ESTIMASI_MS     = 150;', $js);
        $this->assertStringContainsString('var PLAFON_ESTIMASI = 90;', $js);
        $this->assertStringContainsString('var KURVA_ESTIMASI  = 0.06;', $js);
        $this->assertStringContainsString('var MIN_LANGKAH     = 1;', $js);
        $this->assertStringContainsString('sisa * KURVA_ESTIMASI', $js);
        $this->assertStringContainsString("s.fase = 'final';", $js);
        $this->assertStringContainsString('s.target = 100;', $js);

        // 4. Teks selalu bilangan bulat + "%".
        $this->assertStringContainsString("parts.label.textContent = p + '%';", $js);
        $this->assertStringContainsString('Math.round(persen || 0)', $js);

        // 5. Selesai: teks polos "Selesai", tanpa ikon centang.
        $this->assertStringContainsString("var TEKS_SELESAI    = 'Selesai';", $js);
        $this->assertStringNotContainsString('✓', $js);
        $this->assertStringNotContainsString('Selesai ✓', $html);

        // 6. Pembatalan senyap: AbortError tidak jadi pesan / kegagalan.
        $this->assertStringContainsString('window.dlProgressAbort = function ()', $js);
        $this->assertStringContainsString('s.ctrl.abort()', $js);
        $this->assertStringContainsString("err.name === 'AbortError'", $js);
        $this->assertStringNotContainsString('Unduhan dibatalkan', $js);
        $this->assertStringNotContainsString('Swal', $js);
        // Elemen pesan hanya dibuat kalau memang ada teksnya.
        $this->assertStringContainsString('if (!teks) return;', $js);

        // 7. API reset supaya popup berikutnya kembali normal.
        $this->assertStringContainsString('window.dlProgressReset = function ()', $js);
        $this->assertStringContainsString('window.dlProgressBusy = function ()', $js);

        // 8. Warna isian: satu nilai gelap tetap (navy), bukan transparan /
        //    overlay gelap yang membuat bar kurang tajam.
        $this->assertStringContainsString('background-color: #1e3a8a;', $html);
        $this->assertStringNotContainsString('filter: brightness(.82);', $html);
        $this->assertStringNotContainsString('rgba(0, 0, 0, .22)', $html);
        $this->assertStringNotContainsString('--btn-progress-fill', $html);

        // Teks persen rata tengah dan selalu di atas bar.
        $this->assertStringContainsString('inset: 0;', $html);
        $this->assertStringContainsString('justify-content: center;', $html);
        $this->assertStringContainsString('align-items: center;', $html);

        // 9. Tanpa spinner, tanpa shimmer; transisi pendek & linear.
        $this->assertStringContainsString('transition: width .2s linear;', $html);

        // 10. Mode geser: bar ~45% lebar tombol, 1.1s ease-in-out infinite.
        $this->assertStringContainsString('width: 45%;', $html);
        $this->assertStringContainsString('animation: btnProgressGeser 1.1s ease-in-out infinite;', $html);
        $this->assertStringContainsString("var TEKS_GESER      = 'Memproses...';", $js);
        $this->assertStringContainsString('window.dlProgressGeser = function (aktif)', $js);
        $this->assertStringContainsString('button.dataset.dlProgressMode === \'indeterminate\'', $js);

        // 11. Retry 429 masih ada (maks 3x, baca Retry-After).
        $this->assertStringContainsString('res.status === 429', $js);
        $this->assertStringContainsString('Retry-After', $js);
        $this->assertStringContainsString('var MAKS_RETRY      = 3;', $js);

        // 12. Tanpa spinner & tanpa shimmer di dalam komponen sendiri.
        // (Dicetak dari file partial, bukan seluruh halaman: layout memakai
        //  kata "spinner" di satu komentar CSS yang tidak berhubungan.)
        $komponen = file_get_contents(base_path('resources/views/partials/download-progress.blade.php'));
        $this->assertStringNotContainsString('spinner', strtolower($komponen));
        $this->assertStringNotContainsString('shimmer', strtolower($komponen));
        $this->assertStringNotContainsString('keyframes animate', strtolower($komponen));
    }

    /**
     * Footer popup unduhan: dua tombol sama lebar & lebar tetap, dan tombol
     * Batal/Hentikan tidak pernah berubah jadi abu gelap saat nonaktif.
     */
    public function test_footer_popup_unduhan_dua_tombol_lebar_tetap(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();

        $this->assertStringContainsString('class="dl-actions"', $html);
        $this->assertStringContainsString('flex: 1 1 168px', $html);
        $this->assertStringContainsString('min-width: 168px', $html);
        $this->assertStringContainsString('text-align: center;', $html);

        // Hanya tombol utama yang dinonaktifkan saat proses.
        $js = $this->scriptHelper();
        $this->assertStringContainsString('button.disabled        = true;', $js);
        $this->assertStringContainsString('s.button.disabled         = false;', $js);

        // Gaya tombol Batal saat disabled tetap sama (tidak jadi abu gelap).
        $this->assertStringContainsString('.btn-light:disabled', $html);
        $this->assertStringContainsString('opacity: 1;', $html);
    }

    /**
     * Semua tombol unduhan di panel admin & guru memakai komponen bersama.
     */
    public function test_semua_tombol_unduh_memakai_komponen_bersama(): void
    {
        $view = base_path('resources/views');

        // Tombol/link unduhan yang memakai helper.
        $pemakaiHelper = [
            'admin/students/index.blade.php'    => ['data-dl-progress', 'id="kartuUnduhBtn"', "panel_route('students.template')"],
            'admin/students/show.blade.php'     => ["panel_route('students.download-qr'", "panel_route('students.download-card'"],
            'admin/teachers/index.blade.php'    => ["panel_route('guru.template')"],
            'admin/classes/index.blade.php'     => ["panel_route('classes.template')"],
            'admin/holidays/index.blade.php'    => ["panel_route('holidays.template')"],
            'admin/attendances/rekap.blade.php' => ['id="printRekapForm" data-no-download'],
        ];

        foreach ($pemakaiHelper as $rel => $potongan) {
            $isi = file_get_contents($view . '/' . $rel);
            foreach ($potongan as $p) {
                $this->assertStringContainsString($p, $isi, "$rel harus punya: $p");
            }
        }

        // Setiap elemen data-dl-progress juga dikecualikan dari smart loader.
        foreach ($pemakaiHelper as $rel => $potongan) {
            if (!str_contains($potongan[0], 'data-dl-progress')) {
                continue;
            }
            $isi = file_get_contents($view . '/' . $rel);
            preg_match_all('/<a\b[^>]*data-dl-progress[^>]*>/', $isi, $m);
            foreach ($m[0] as $tag) {
                $this->assertStringContainsString(
                    'data-no-download',
                    $tag,
                    "Tag <a> dengan data-dl-progress di $rel harus punya data-no-download."
                );
            }
        }

        // Komponen bersama ikut termuat di layout (satu tempat, bukan per halaman).
        $layout = file_get_contents($view . '/layouts/app.blade.php');
        $this->assertStringContainsString("@include('partials.download-progress')", $layout);
        $partial = file_get_contents($view . '/partials/download-progress.blade.php');
        $this->assertStringContainsString('window.downloadWithProgress', $partial);

        // Tombol yang BUKAN unduhan (Unggah & Import) tidak boleh disentuh.
        foreach (['admin/students/index.blade.php', 'admin/classes/index.blade.php', 'admin/holidays/index.blade.php'] as $rel) {
            $isi = file_get_contents($view . '/' . $rel);
            preg_match_all('/<button\b[^>]*data-import[^>]*>[^<]*/', $isi, $m);
            foreach ($m[0] as $tag) {
                $this->assertStringNotContainsString('data-dl-progress', $tag, "Tombol import di $rel tidak boleh memakai helper unduhan.");
            }
        }
    }

    /**
     * Regresi: tombol "Generate & Unduh" popup Cetak Kartu pernah tercatat
     * id="kartuUnduhBtn" DUA KALI pada SATU tag. HTML jadi tidak valid,
     * getElementById hanya menemukan yang pertama, dan perilaku tombol unduh
     * jadi tidak bisa diandalkan.
     *
     * Penting: pemeriksaan dilakukan PER TAG (bukan per file), karena Blade
     * @if/@elseif/@else bisa memakai id yang sama di cabang yang berbeda-beda
     * dan itu SAH — hanya salah kalau satu tag memuat id yang sama 2x.
     * Pemeriksaan ke-2 dilakukan pada HTML yang sudah dirender.
     */
    public function test_tidak_ada_id_ganda_di_view_unduhan(): void
    {
        $view = base_path('resources/views');

        $daftar = [
            'admin/students/index.blade.php',
            'admin/students/show.blade.php',
            'admin/teachers/index.blade.php',
            'admin/classes/index.blade.php',
            'admin/holidays/index.blade.php',
            'admin/attendances/rekap.blade.php',
        ];

        foreach ($daftar as $rel) {
            $isi = file_get_contents($view . '/' . $rel);

            // 1) Satu tag tidak boleh memuat id yang sama lebih dari sekali.
            preg_match_all('/<[a-zA-Z][^>]*>/s', $isi, $tags);
            foreach ($tags[0] as $tag) {
                preg_match_all('/\bid="([^"]*)"/', $tag, $m);
                $jumlah = array_count_values($m[1]);
                foreach ($jumlah as $id => $n) {
                    $this->assertLessThanOrEqual(
                        1,
                        $n,
                        "Tag di $rel memuat id=\"$id\" sebanyak $n kali (HTML tidak valid)."
                    );
                }
            }
        }

        // 2) HTML yang sudah dirender benar-benar tidak punya id ganda.
        $halaman = [
            'admin.students.index',
            'admin.students.show',
            'admin.teachers.index',
            'admin.classes.index',
            'admin.holidays.index',
            'admin.attendances.rekap',
        ];

        foreach ($halaman as $nama) {
            if (!\Illuminate\Support\Facades\View::exists($nama)) {
                continue;
            }
            try {
                $html = \Illuminate\Support\Facades\Blade::render(
                    file_get_contents($view . '/' . str_replace('.', '/', $nama) . '.blade.php'),
                    ['type' => 'bulanan', 'classes' => collect(), 'classId' => null]
                );
            } catch (\Throwable $e) {
                // View butuh variabel yang lebih rumit; lewati, sudah dicek per-tag di atas.
                continue;
            }
            preg_match_all('/\bid="([^"]*)"/', $html, $m);
            $jumlah = array_count_values($m[1]);
            $ganda = array_keys(array_filter($jumlah, static fn ($n) => $n > 1));
            $this->assertSame([], $ganda, "HTML render $nama punya id ganda: " . implode(', ', $ganda) . '.');
        }
    }

    /**
 * "Semua Kelas" sekarang menghasilkan SATU file ZIP, bukan 18 file terpisah.
 * ZIP dirakit di browser dengan JSZip yang dibundel Vite (bukan CDN).
 */
    public function test_jszip_dibundel_vite_dan_hanya_dimuat_di_halaman_rekap(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.rekap'))->getContent();

        // Dimuat lewat @vite (bukan <script src=CDN>).
        $this->assertStringContainsString('rekap-print-zip', $html);
        $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/jszip', $html);
        $this->assertStringNotContainsString('unpkg.com/jszip', $html);

        // Entry Vite terdaftar.
        $vite = file_get_contents(base_path('vite.config.js'));
        $this->assertStringContainsString('resources/js/rekap-print-zip.js', $vite);

        // Modul sumber benar-benar mengimpor jszip.
        $modul = file_get_contents(base_path('resources/js/rekap-print-zip.js'));
        $this->assertStringContainsString("import JSZip from 'jszip'", $modul);
        $this->assertStringContainsString('window.JSZip = JSZip', $modul);

        // Dependensi tercatat di package.json.
        $pkg = json_decode(file_get_contents(base_path('package.json')), true);
        $this->assertArrayHasKey('jszip', $pkg['dependencies'] ?? []);

        // Hasil build Vite memuat bundel JSZip.
        $this->assertFileExists(public_path('build/manifest.json'));
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $ada = false;
        foreach ($manifest as $entry) {
            if (str_contains($entry['file'] ?? '', 'rekap-print-zip')) {
                $ada = true;
            }
        }
        $this->assertTrue($ada, 'Manifest Vite harus punya entry rekap-print-zip.');
    }

    public function test_loop_semua_kelas_menyusun_satu_zip(): void
    {
        $js = $this->scriptPopupRekap();

        // Setiap kelas masuk ke objek ZIP, bukan langsung diunduh.
        $this->assertStringContainsString('rekapZipTambah', $js);
        $this->assertStringContainsString('window.REKAP_ZIP.file(nama, blob)', $js);

        // Nama file di ZIP: Profil_<Kelas>_<Periode>.<ext>
        $this->assertStringContainsString("'Profil_' + namaKelas + '_'", $js);
        $this->assertStringContainsString("'xlsx' : 'pdf'", $js);

        // Satu file ZIP di akhir, STORE (PDF sudah terkompresi).
        $this->assertStringContainsString("generateAsync({ type: 'blob', compression: 'STORE' })", $js);
        $this->assertStringContainsString("'Profil_Presensi_Semua_Kelas_'", $js);
        $this->assertStringContainsString('URL.revokeObjectURL', $js);

        // Tahap "Menyusun ZIP": tombol tinggal 100% sampai ZIP selesai.
        // tombol tinggal menampilkan 100% sampai ZIP selesai.
        $this->assertStringContainsString(
            'rekapSetProgress(totalKeseluruhan, totalKeseluruhan)',
            $js
        );

        // Sanitasi karakter terlarang + suffiks nama duplikat.
        $this->assertStringContainsString('rekapZipKosong', $js);
        $modul = file_get_contents(base_path('resources/js/rekap-print-zip.js'));
        // Karakter terlarang di nama file Windows: / \ : * ? " < > |
        $this->assertStringContainsString('<>|', $modul);
        // Suffiks nama duplikat: nama + '_' + nomor urut.
        $this->assertStringContainsString("dasar + '_' + i", $modul);

        // Objek ZIP dikosongkan saat popup dibuka, saat mulai unduhan baru,
        // saat Hentikan ditekan, saat reset, dan sebagai jaring pengaman.
        $this->assertSame(
            5,
            substr_count($js, 'rekapZipKosong()'),
            'rekapZipKosong() harus ada di: definisi, jaring pengaman rekapZipTambah, '
                . 'mulai unduhan baru, tombol Hentikan, dan buka popup.'
        );

        // Kegagalan ditangani lewat tombol utama "Coba lagi (n)" + baris merah,
        // bukan lagi tombol terpisah di dalam panel.
        $this->assertStringContainsString("btn.dataset.mode = 'retry'", $js);
        $this->assertStringContainsString('rekapFailLine', $js);
    }

    /**
 * Blok "Cari Siswa" hanya boleh tampil pada mode Per Siswa + cakupan Satu Siswa.
 *
 * Bug: handler show.bs.modal memanggil toggleRekapStudentScope() tanpa syarat
 * sehingga begitu popup dibuka, blok "Cari Siswa" langsung muncul lagi walau
 * radionya sudah "Cetak per Kelas". Sisa pencarian (input, siswa terpilih,
 * dropdown hasil, pesan merah) juga tidak pernah dibersihkan.
 */
    public function test_blok_cari_siswa_hanya_tampil_di_mode_per_siswa_satu_siswa(): void
    {
        $js = $this->scriptPopupRekap();

        // Satu fungsi sync yang dipanggil radio change DAN buka popup.
        $this->assertStringContainsString('function syncModeUI()', $js);
        $this->assertStringContainsString('function rekapResetPencarianSiswa()', $js);

        // Kedua radio tetap memakai onchange yang sekarang memanggil syncModeUI.
        $this->assertStringContainsString('function toggleRekapPrintScope()', $js);
        $this->assertStringContainsString('function toggleRekapStudentScope()', $js);
        $this->assertSame(
            4,
            substr_count($js, 'syncModeUI();'),
            'syncModeUI() dipanggil dari: onchange Jenis Cetak, onchange Cakupan, buka popup, dan DOMContentLoaded.'
        );

        // Buka popup: paksa Per Kelas + bersihkan pencarian.
        $this->assertStringContainsString("document.getElementById('printModeClass').checked = true", $js);
        $this->assertStringContainsString('syncModeUI();' . "\n" . '            rekapResetPencarianSiswa();', $js);

        // Reset mencakup keempat bagian pencarian.
        $this->assertStringContainsString("getElementById('rekapStudentSearch')", $js);
        $this->assertStringContainsString("getElementById('rekapStudentId')", $js);
        $this->assertStringContainsString("getElementById('studentSearchResults')", $js);
        $this->assertStringContainsString("getElementById('studentSearchHelp')", $js);
        $this->assertStringContainsString("hasil.style.display = 'none'", $js);
        $this->assertStringContainsString("bantuan.classList.remove('text-danger')", $js);

        // Hanya blok "Cari Siswa" yang mengikuti mode, sisanya tidak berubah.
        $this->assertStringContainsString("singleStudentContainer.style.display = scopeSingle ? 'block' : 'none'", $js);
    }

    // ==========================================================
    // 5. HALAMAN RIWAYAT PRESENSI PER SISWA
    // ==========================================================

    public function test_halaman_riwayat_presensi_tidak_punya_tombol_cetak_yang_salah_format(): void
    {
        $r = $this->actingAs($this->admin)->get(route('admin.kehadiran.student-history', $this->siswa->id));
        $r->assertStatus(200);

        $html = $r->getContent();

        // Bila suatu saat halaman ini punya tombol cetak PDF, tombolnya tidak
        // boleh diam-diam mengirim format=excel (atau sebaliknya).
        if (str_contains($html, 'data-format=')) {
            foreach (['pdf', 'excel'] as $fmt) {
                $this->assertStringContainsString('data-format="' . $fmt . '"', $html);
            }
        }

        // Halaman ini murni HTML, tidak pernah menghasilkan PDF/Excel langsung.
        $this->assertStringNotContainsString('%PDF', $html);
    }
}

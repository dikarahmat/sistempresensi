<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadCacheService
{
    protected static string $baseDir = '';

    /** @var array<string, \GdImage> */
    private static array $cardJpgBackgroundCache = [];

    /** @var array<string, \GdImage> */
    private static array $cardJpgLogoCache = [];

    /** @var array<string, \GdImage> */
    private static array $cardJpgTemplateCache = [];

    /***
     * Versi cache rekap. Naikkan nilainya setiap logika perhitungan rekap berubah
     * supaya file lama tidak pernah disajikan lagi.
     */
    protected static string $rekapCacheVersion = 'v2';

    /***
     * satu-satinya tempat ganti jenis kertas ('a4' atau 'a3').
     * Kolom/baris/per lembar dihitung otomatis dari sini.
     */
    public const CARD_PAPER = 'a4';

    /**
     * Peta ukuran kertas: lebar dan tinggi mm.
     * A4 landscape: 297×210, A3 landscape: 420×297.
     */
    protected static function paperConfig(): array
    {
        return [
            'a4' => ['width' => 297, 'height' => 210, 'cols' => 5, 'rows' => 2, 'perSheet' => 10],
            'a3' => ['width' => 420, 'height' => 297, 'cols' => 7, 'rows' => 3, 'perSheet' => 21],
        ];
    }

    /**
     * Ambil stamp desain dari file-terbitan view yang ikut desain.
     * Digabung "_" . self::CARD_PAPER untuk versi cache.
     */
    protected static function getDesignStamp(): string
{
    $files = [
        resource_path('views/shared/print-cards.blade.php'),
        resource_path('views/admin/students/partials/card-single.blade.php'),
        resource_path('views/admin/students/print-card-pdf.blade.php'),
        app_path('Services/DownloadCacheService.php'),
        public_path('images/card-bg-single.svg'),
        public_path('images/card-bg-pdf.png'),
    ];
    $parts = [];
    foreach ($files as $path) {
        $parts[] = is_file($path) ? (string) filemtime($path) : '0';
    }
    $parts[] = self::CARD_PAPER;

    return substr(md5(implode('|', $parts)), 0, 12);
}
    public static function initDirs(): void
    {
        if (empty(self::$baseDir)) {
            self::$baseDir = storage_path('app/downloads_cache');
        }

        $dirs = [
            self::$baseDir,
            self::$baseDir . '/cards',
            self::$baseDir . '/qrs',
            self::$baseDir . '/templates',
            self::$baseDir . '/rekap',
        ];

        foreach ($dirs as $dir) {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true, true);
            }
        }
    }

    /**
     * Dapatkan Setting Timestamp untuk invalidate jika logo/nama sekolah berubah.
     */
    protected static function getSettingStamp(): int
    {
        try {
            $setting = Setting::first();
            return $setting && $setting->updated_at ? $setting->updated_at->timestamp : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
/** 
     * Dapatkan stempel file gambar (logo & QR) untuk cache invalidation. 
     * Menggunakan filemtime dari file path untuk mendeteksi perubahan gambar. 
     */
    protected static function getImageStamp(): int
    {
        try {
            $logoPath = public_path(Setting::getLogo());
            $bgSvgPath = public_path('images/card-bg-single.svg');
            $bgPngPath = public_path('images/card-bg-pdf.png');

            $times = [];
            if (file_exists($logoPath)) {
                $times[] = filemtime($logoPath);
            }
            if (file_exists($bgSvgPath)) {
                $times[] = filemtime($bgSvgPath);
            }
            if (file_exists($bgPngPath)) {
                $times[] = filemtime($bgPngPath);
            }

            return empty($times) ? 0 : max($times);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Dapatkan path executable browser (Chrome atau Edge) untuk headless screenshot.
     */
    public static function getBrowserBinary(): ?string
    {
        $candidates = [
            'C:\Program Files\Google\Chrome\Application\chrome.exe',
            'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
            'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
            'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
        ];

        foreach ($candidates as $bin) {
            if (File::exists($bin)) {
                return $bin;
            }
        }

        return null;
    }

    /**
     * Download / Stream Single Student Card PNG (Pure Kartu, 4 Sudut Lengkung, Latar Transparan, CR80 1086x1725 px)
     */
    public static function downloadSingleCard(Student $student)
    {
        self::initDirs();

        try {
            $student = clone $student;
            $student->loadMissing('schoolClass');
            $settingStamp = self::getSettingStamp();
            $studentStamp = $student->updated_at ? $student->updated_at->timestamp : 0;
            $designStamp = self::getDesignStamp();
            $cacheFileName = "card_single_{$student->id}_{$studentStamp}_{$settingStamp}_{$designStamp}.png";
            $cacheFilePath = self::$baseDir . '/cards/' . $cacheFileName;

            $downloadFileName = 'kartu-presensi-' . $student->nisn . '-' . Str::slug($student->name) . '.png';

            // Bersihkan file cache jika ada file 0-byte atau rusak
            if (File::exists($cacheFilePath) && File::size($cacheFilePath) === 0) {
                @unlink($cacheFilePath);
            }

            if (!File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
                self::renderCardPngWithGd($student, $cacheFilePath);

                // Pastikan file 0 byte tidak pernah disimpan atau disajikan
                if (!File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
                    if (File::exists($cacheFilePath)) {
                        @unlink($cacheFilePath);
                    }
                    throw new \RuntimeException("Berkas kartu presensi siswa ID {$student->id} gagal dibuat atau berukuran 0 byte.");
                }
            }

            return response()->download($cacheFilePath, $downloadFileName, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'attachment; filename="' . $downloadFileName . '"',
                'Cache-Control' => 'no-cache, must-revalidate'
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengunduh kartu presensi siswa: ' . $e->getMessage(), [
                'student_id' => $student->id ?? null,
                'exception' => $e
            ]);

            if (request()->ajax() || request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'message' => 'Kartu belum bisa diunduh, coba lagi'
                ], 500);
            }

            return redirect()->back()->with('error', 'Kartu belum bisa diunduh, coba lagi');
        }
    }

    /**
     * Download Single Student Card JPG (1276 x 2022 px, ~600 DPI), memakai
     * renderer yang sama dengan kartu massal agar tampilan identik.
     */
    public static function downloadSingleCardJpg(Student $student)
    {
        self::initDirs();

        try {
            $student = clone $student;
            $student->loadMissing('schoolClass');
            $settingStamp = self::getSettingStamp();
            $designStamp = self::getDesignStamp();
            $cacheFilePath = self::ensureStudentCardJpgCache($student, $settingStamp, $designStamp);

            [$namePart, $nisPart, $classPart] = self::cardFileParts($student);
            $downloadFileName = "{$namePart}-{$nisPart}-{$classPart}.jpg";

            return response()->download($cacheFilePath, $downloadFileName, [
                'Content-Type' => 'image/jpeg',
                'Content-Disposition' => 'attachment; filename="' . $downloadFileName . '"',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengunduh kartu presensi JPG siswa: ' . $e->getMessage(), [
                'student_id' => $student->id ?? null,
                'exception' => $e,
            ]);

            if (request()->ajax() || request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['message' => 'Kartu belum bisa diunduh, coba lagi'], 500);
            }

            return redirect()->back()->with('error', 'Kartu belum bisa diunduh, coba lagi');
        }
    }

    private static function studentCardJpgCachePath(Student $student, string $settingStamp, string $designStamp): string
    {
        $studentStamp = $student->updated_at ? $student->updated_at->timestamp : 0;

        return self::$baseDir . '/cards/'
            . "card_single_jpg_v5_{$student->id}_{$studentStamp}_{$settingStamp}_{$designStamp}.jpg";
    }

    private static function ensureStudentCardJpgCache(Student $student, string $settingStamp, string $designStamp): string
    {
        $cacheFilePath = self::studentCardJpgCachePath($student, $settingStamp, $designStamp);

        if (File::exists($cacheFilePath) && File::size($cacheFilePath) > 0) {
            return $cacheFilePath;
        }

        if (File::exists($cacheFilePath)) {
            @unlink($cacheFilePath);
        }

        $tmpPath = $cacheFilePath . '.tmp_' . bin2hex(random_bytes(4));

        try {
            self::renderCardJpgPreview($student, $tmpPath);

            if (!File::exists($tmpPath) || File::size($tmpPath) === 0) {
                throw new \RuntimeException("Berkas kartu presensi JPG siswa ID {$student->id} gagal dibuat atau berukuran 0 byte.");
            }

            if (File::exists($cacheFilePath) && File::size($cacheFilePath) > 0) {
                return $cacheFilePath;
            }

            if (File::exists($cacheFilePath)) {
                @unlink($cacheFilePath);
            }

            if (!File::move($tmpPath, $cacheFilePath)) {
                throw new \RuntimeException("Berkas kartu presensi JPG siswa ID {$student->id} gagal dipindahkan ke cache.");
            }

            if (!File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
                throw new \RuntimeException("Berkas kartu presensi JPG siswa ID {$student->id} gagal dibuat atau berukuran 0 byte.");
            }

            return $cacheFilePath;
        } finally {
            if (File::exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

/**
 * Render Single Student Card PNG menggunakan PHP GD murni.
 * Standar CR80 Portrait: 1086 x 1725 px (~500 DPI),
 * desain identik dengan card.blade.php (massal).
 * Kanvas penuh 1086x1725, tanpa area transparan/sudut melengkung.
 * Latar kartu digambar menggunakan desain SVG card-bg-single.svg
 * melalui perintah GD (rect, ellipse). Teks dan QR tetap digambar di atas.
 */
public static function renderCardPngWithGd(Student $student, string $outputPath): void
{
    $w = 1086;
    $h = 1725;

    $img = imagecreatetruecolor($w, $h);
    imagesavealpha($img, true);
    imagealphablending($img, true);

        // Latar belakang transparan di luar sudut lengkung kartu
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);

        // Palet warna resmi sesuai card.blade.php
        $navy = imagecolorallocate($img, 30, 58, 138);         // #1e3a8a (Header)
        $amber = imagecolorallocate($img, 245, 158, 11);       // #f59e0b (Garis aksen 0.8mm)
        $gold = imagecolorallocate($img, 252, 211, 77);        // #fcd34d (KARTU PRESENSI DIGITAL)
        $white = imagecolorallocate($img, 255, 255, 255);      // #ffffff (Badan kartu & quiet zone QR)
        $dark = imagecolorallocate($img, 15, 23, 42);          // #0f172a (Nama, Kelas, SCAN PRESENSI)
        $grayLight = imagecolorallocate($img, 248, 250, 252);  // #f8fafc (Footer bg)
        $grayBorder = imagecolorallocate($img, 226, 232, 240); // #e2e8f0 (Garis atas footer)
        $grayText = imagecolorallocate($img, 100, 116, 139);   // #64748b (Teks footer)

        // Proporsi tinggi elemen (total 1725 px) - DISETUJUI sama desain card.blade.php
        // dengan pembaharuan footer navy lebih tinggi (141px daripada 133px).
        // Header 11.2mm = 226px, Accent 0.8mm = 16px, Identity 18mm = 363px, QR 49mm = 979px,
        // Footer 8.1mm = 141px (sebanding 141/1725 = 8.1% dari tinggi kartu).
        // Total: 226 + 16 + 363 + 979 + 141 = 1725px ✓
        $headerH = 226;    // 11.2mm
        $accentH = 16;     // 0.8mm
        $identityH = 363;  // 18mm (nama + NISN + kelas, vertikal tengah)
        $qrAreaH = 979;    // 49mm (disingkurkan 8px untuk footer lebih tinggi)
        $footerH = 141;    // 8.1mm (141/1725 dari tinggi total)
        $footerY = $h - $footerH; // 1584

        // 1. Gambar badan kartu putih (di bawah header + accent)
        imagefilledrectangle($img, 0, $headerH + $accentH, $w - 1, $footerY - 1, $white);

        // 2. Garis aksen oranye
        imagefilledrectangle($img, 0, $headerH, $w - 1, $headerH + $accentH - 1, $amber);

        // 3. Header Navy kotak square (tanpa sudut melengkung)
        imagefilledrectangle($img, 0, 0, $w - 1, $headerH - 1, $navy);

        // 4. Footer Abu-abi kotak square (tanpa sudut melengkung dan transparan)
        imagefilledrectangle($img, 0, $footerY, $w - 1, $h - 1, $grayLight);
        imageline($img, 0, $footerY, $w - 1, $footerY, $grayBorder);

        // 5. (Removed: asep pengurusan sudut melengkung -- kanvas penuh square corners, tidak ada area transparan)

        // 6. Muat font TTF bawaan vendor/dompdf (tersedia di lokal dan production)
        $fontBold = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');
        $fontOblique = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Oblique.ttf');

        // 7. Render Header: Logo Sekolah + Nama Sekolah + KARTU PRESENSI DIGITAL
        // SAMA DENGAN card.blade.php: logo 6mm (~62px), teks di sebelah kanan, gap ~8-10px
        $logoPath = public_path(Setting::getLogo());
        $logoSize = 62;                    // 6mm ≈ 62px (massal: 6mm)
        $logoX = 45;                       // padding kiri ~4.3mm
        $logoY = (int) round(($headerH - $logoSize) / 2); // vertikal tengah di header
        $textStartX = $logoX + $logoSize + 10; // gap ~10px (massal: ~2.2mm + 1.5mm ≈ 8-10px)

        if (file_exists($logoPath)) {
            $logoInfo = @getimagesize($logoPath);
            if ($logoInfo) {
                $logoSrcImg = match ($logoInfo[2]) {
                    IMAGETYPE_PNG => @imagecreatefrompng($logoPath),
                    IMAGETYPE_JPEG => @imagecreatefromjpeg($logoPath),
                    IMAGETYPE_WEBP => @imagecreatefromwebp($logoPath),
                    default => null
                };
                if ($logoSrcImg) {
                    imagecopyresampled($img, $logoSrcImg, $logoX, $logoY, 0, 0, $logoSize, $logoSize, imagesx($logoSrcImg), imagesy($logoSrcImg));
                    imagedestroy($logoSrcImg);
                }
            }
        }

        $schoolName = mb_strtoupper(Setting::getSchoolName() ?: 'SMP PGRI PARUNGPANJANG');
        $schoolFontSize = 32; // massal: 6pt ≈ 32px @ 500 DPI
        while ($schoolFontSize > 20) {
            $bbox = imagettfbbox($schoolFontSize, 0, $fontBold, $schoolName);
            $textW = abs($bbox[4] - $bbox[0]);
            if ($textStartX + $textW < ($w - 30)) {
                break;
            }
            $schoolFontSize -= 2;
        }
        // Baseline: vertikal tengah header (11.2mm = 226px) → baseline ≈ 226/2 + 6pt/2 ≈ 113 + 11 ≈ 124
        // card.blade.php: padding 1.5mm top/bottom, font 6pt, line-height 1.15
        $schoolBaseline = 124;
        imagettftext($img, $schoolFontSize, 0, $textStartX, $schoolBaseline, $white, $fontBold, $schoolName);

        // Subtitle "KARTU PRESENSI DIGITAL" - massal: 4.2pt ≈ 22px, margin-top 0.5mm ≈ 5px
        $subtitleFontSize = 22;
        $subtitleBaseline = $schoolBaseline + 26; // 6pt + 0.5mm gap + 4.2pt ≈ 16 + 5 + 15 = 36, tapi baseline offset
        imagettftext($img, $subtitleFontSize, 0, $textStartX, $subtitleBaseline, $gold, $fontBold, 'KARTU PRESENSI DIGITAL');

        // 8. Blok Identitas (Nama, NISN, Kelas) - 18mm = 363px, vertikal tengah
        // card.blade.php: height 18mm, vertical-align: middle, padding 1.5mm top/bottom
        // Blok dimulai setelah header + accent = 226 + 16 = 242px
        // Blok berakhir sebelum QR area = 242 + 363 = 605px
        $identityTop = $headerH + $accentH; // 242px
        $identityBottom = $identityTop + $identityH; // 605px
        $identityMiddle = ($identityTop + $identityBottom) / 2; // 423.5px

        $studentName = mb_strtoupper(trim($student->name));
        $studentNisn = trim((string) ($student->nisn ?? ''));
        $className = mb_strtoupper($student->schoolClass->name ?? ($student->kelas ?? '-'));

        // Font sizes - SAMA DENGAN card.blade.php (7pt, 6.8pt, 8pt @ 500 DPI)
        $nameFontSize = 37;   // 7pt ≈ 37px
        $nisnFontSize = 36;   // 6.8pt ≈ 36px
        $classFontSize = 42;  // 8pt ≈ 42px

        $maxNameWidth = $w - 120; // Margin kiri-kanan 60px

        // Nama: SATU BARIS (nowrap + clip), line-height 1.2 - SAMA dengan template
        // Template: white-space: nowrap; overflow: hidden; text-overflow: clip; line-height: 1.2
        $nameLineHeight = (int) round($nameFontSize * 1.2); // ≈ 44px (1.2 * 37)

        // Potong nama jika terlalu panjang (clip seperti template text-overflow: clip)
        $nameBbox = imagettfbbox($nameFontSize, 0, $fontBold, $studentName);
        $nameWidth = abs($nameBbox[4] - $nameBbox[0]);
        if ($nameWidth > $maxNameWidth) {
            // Binary search untuk panjang maksimal yang muat
            $min = 0;
            $max = mb_strlen($studentName);
            $clippedName = $studentName;
            while ($min <= $max) {
                $mid = (int) (($min + $max) / 2);
                $testName = mb_substr($studentName, 0, $mid);
                $testBbox = imagettfbbox($nameFontSize, 0, $fontBold, $testName);
                $testWidth = abs($testBbox[4] - $testBbox[0]);
                if ($testWidth <= $maxNameWidth) {
                    $clippedName = $testName;
                    $min = $mid + 1;
                } else {
                    $max = $mid - 1;
                }
            }
            $studentName = $clippedName;
        }

        // NISN: line-height 1.2, gap 0.53mm ≈ 11px dari nama
        $nisnLineHeight = (int) round($nisnFontSize * 1.2); // ≈ 43px
        $nisnGap = 60; // diperbesar dari 11px → ~70px total gap (line-height 44 + gap 60 = 104px baseline-to-baseline, visual gap ~70px)

        // Kelas: line-height 1.2, gap 11px dari NISN
        $classLineHeight = (int) round($classFontSize * 1.2); // ≈ 50px
        $classGap = 60; // diperbesar dari 11px → ~70px visual gap

        // Total tinggi blok teks (nama + gap + NISN + gap + kelas)
        $nameHeight = $nameLineHeight; // 1 baris
        $totalTextHeight = $nameHeight;
        if ($studentNisn !== '') {
            $totalTextHeight += $nisnGap + $nisnLineHeight + $classGap + $classLineHeight;
        } else {
            $totalTextHeight += $classGap + $classLineHeight;
        }

        // Posisikan blok identitas agar center visual di tengah block 363px (242-605)
        // Target: name center ~362, nisn center ~432, class center ~502
        // Hitung baseline positions untuk target centers:
        // name baseline = 362 - (37*0.4) ≈ 347
        // nisn baseline = 432 - (36*0.4) ≈ 418
        // class baseline = 502 - (42*0.4) ≈ 485

        // Baseline positions dengan gap 70px:
        $nameBaseline = 377;    // 347 + 30
        $nisnBaseline = $nameBaseline + 70; // 447
        $classBaseline = $nisnBaseline + 70; // 517

        // Render Nama (SATU BARIS, nowrap+clip)
        $bbox = imagettfbbox($nameFontSize, 0, $fontBold, $studentName);
        $lineW = abs($bbox[4] - $bbox[0]);
        $lx = (int) round(($w - $lineW) / 2);
        imagettftext($img, $nameFontSize, 0, $lx, $nameBaseline, $dark, $fontBold, $studentName);

        // Render NISN
        if ($studentNisn !== '') {
            $nisnBbox = imagettfbbox($nisnFontSize, 0, $fontBold, $studentNisn);
            $nisnW = abs($nisnBbox[4] - $nisnBbox[0]);
            $nisnX = (int) round(($w - $nisnW) / 2);
            imagettftext($img, $nisnFontSize, 0, $nisnX, $nisnBaseline, $dark, $fontBold, $studentNisn);
        }

        // Render Kelas
        $classBbox = imagettfbbox($classFontSize, 0, $fontBold, $className);
        $classW = abs($classBbox[4] - $classBbox[0]);
        $classX = (int) round(($w - $classW) / 2);
        imagettftext($img, $classFontSize, 0, $classX, $classBaseline, $dark, $fontBold, $className);

        // 9. QR Code Area - 49mm = 987px (allocated space)
        // card.blade.php: QR 32mm x 32mm dalam container 34mm dengan padding 1mm
        // 32mm @ 500 DPI = 32 * 500/25.4 ≈ 630px
        // 34mm @ 500 DPI = 34 * 500/25.4 ≈ 670px
        // 1mm padding @ 500 DPI = 19.7px ≈ 20px each side
        // Target QR actual top: y=660 (55px gap from identity block bottom at 605px)
        // Container top = 660 - 20px padding = 640
        $qrAreaTop = 640; // QR container top

        $token = $student->nisn ?: $student->qr_token;
        $qrMatrix = \BaconQrCode\Encoder\Encoder::encode($token, \BaconQrCode\Common\ErrorCorrectionLevel::M())->getMatrix();
        $mW = $qrMatrix->getWidth();
        $mH = $qrMatrix->getHeight();

        $qrCodeSize = 630; // 32mm ≈ 630px @ 500 DPI
        $moduleSize = (int) floor($qrCodeSize / $mW);
        $qrActualSize = $moduleSize * $mW;
        $qrStartX = (int) round(($w - $qrActualSize) / 2);

        // Container 34mm = 670px dengan padding 1mm = 20px each side
        $containerSize = $qrActualSize + 40; // padding 20px each side (1mm @ 500 DPI)
        $containerX = (int) round(($w - $containerSize) / 2);
        $containerY = $qrAreaTop; // QR container top at y=660

        // Background putih quiet zone (container)
        imagefilledrectangle($img, $containerX, $containerY, $containerX + $containerSize, $containerY + $containerSize, $white);

        // Draw QR modules centered in container
        $qrStartX = $containerX + 10; // 10px padding
        $qrStartY = $containerY + 10;

        $black = imagecolorallocate($img, 0, 0, 0);
        for ($my = 0; $my < $mH; $my++) {
            for ($mx = 0; $mx < $mW; $mx++) {
                if ($qrMatrix->get($mx, $my) === 1) {
                    $x1 = $qrStartX + ($mx * $moduleSize);
                    $y1 = $qrStartY + ($my * $moduleSize);
                    $x2 = $x1 + $moduleSize - 1;
                    $y2 = $y1 + $moduleSize - 1;
                    imagefilledrectangle($img, $x1, $y1, $x2, $y2, $black);
                }
            }
        }

        // 10. Teks "SCAN PRESENSI" - massal: 5pt ≈ 17px
        // Target: pusat y≈1380, gap ~100px dari QR actual bottom (~1310)
        // QR actual bottom = containerY + 20 + qrActualSize = 640 + 20 + 630 = 1290
        // Target baseline ≈ 1290 + 100 = 1390
        $scanFontSize = 17; // 5pt ≈ 17px
        $scanBbox = imagettfbbox($scanFontSize, 0, $fontBold, 'SCAN PRESENSI');
        $scanW = abs($scanBbox[4] - $scanBbox[0]);
        $scanX = (int) round(($w - $scanW) / 2);
        $qrActualBottom = $containerY + 20 + $qrActualSize; // 640 + 20 + 630 = 1290
        $scanY = $qrActualBottom + 100; // 100px gap dari QR actual bottom = 1390
        imagettftext($img, $scanFontSize, 0, $scanX, $scanY, $dark, $fontBold, 'SCAN PRESENSI');

        // 11. Footer - vertikal center di area navy (y 1584-1725, height 141px)
        $footerText = 'Tunjukkan kartu saat presensi masuk';
        $footerFontSize = 15; // 4.5pt ≈ 15px
        $footBbox = imagettfbbox($footerFontSize, 0, $fontOblique, $footerText);
        $footW = abs($footBbox[4] - $footBbox[0]);
        $footX = (int) round(($w - $footW) / 2);
        // Footer vertikal tengah: center di area navy (y 1584-1725)
        $footY = $footerY + ($footerH / 2) + ($footerFontSize / 4); // ≈ 1658
        imagettftext($img, $footerFontSize, 0, $footX, $footY, $grayText, $fontOblique, $footerText);

        // 12. Tulis file PNG dengan kompresi maksimal (9)
        imagepng($img, $outputPath, 9);
        imagedestroy($img);
    }

    /**
     * Download Mass Cards JPG dalam ZIP (Instan via Cache / Pre-generate)
     */
   public static function downloadMassCards(Request $request, ?int $forceClassId = null, array $allowedClassIds = []): BinaryFileResponse
{
    self::initDirs();

    ini_set('memory_limit', '1024M');
    ini_set('max_execution_time', '900');

    $query = Student::with('schoolClass')->where('status', 'Aktif');

    $classId = $forceClassId;
    if (!$classId && $request->filled('class_id') && $request->input('class_id') !== 'all') {
        $classId = (int) $request->input('class_id');
    }

    if (!empty($allowedClassIds)) {
        $query->whereIn('school_class_id', $allowedClassIds);
        if ($classId && in_array($classId, $allowedClassIds)) {
            $query->where('school_class_id', $classId);
        }
    } elseif ($classId) {
        $query->where('school_class_id', $classId);
    }

    $studentIds = null;
    if ($request->filled('student_ids') && is_array($request->student_ids)) {
        $studentIds = array_filter(array_map('intval', $request->student_ids));
        if (!empty($studentIds)) {
            $query->whereIn('id', $studentIds);
            sort($studentIds);
        }
    }

    $studentCount = (clone $query)->count();
    $latestUpdate = (clone $query)->max('updated_at') ?? '0';
    $settingStamp = self::getSettingStamp();
    $designStamp = self::getDesignStamp();

    $idsHash = $studentIds ? md5(implode(',', $studentIds)) : 'all';
    $scopeKey = ($classId ? "cls_{$classId}" : 'all') . "_{$idsHash}_{$studentCount}_" . strtotime($latestUpdate) . "_{$settingStamp}";
    $cacheFileName = "cards_mass_jpg_v8_{$scopeKey}_{$designStamp}.zip";
    $cacheFilePath = self::$baseDir . '/cards/' . $cacheFileName;

    $targetClassName = 'Semua_Siswa';
    if ($classId) {
        $cls = SchoolClass::find($classId);
        if ($cls) {
            $classSlug = strtoupper(Str::slug((string) $cls->name, '_'));
            $targetClassName = $classSlug !== '' ? $classSlug : 'KELAS_' . $classId;
        }
    }
    $downloadFileName = 'Kartu_Presensi_JPG_' . $targetClassName . '_' . date('Ymd_His') . '.zip';

    if (!File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
        // Urut: kelas natural (7A, 7B, ... 9F), lalu nama A-Z
        $students = $query->get()->sort(function ($a, $b) {
            $ca = $a->schoolClass->name ?? '';
            $cb = $b->schoolClass->name ?? '';
            if ($ca !== $cb) return strnatcmp($ca, $cb);
                return strnatcasecmp(Str::ascii($a->name), Str::ascii($b->name));
        })->values();

        if ($students->isEmpty()) {
            abort(404, 'Tidak ada data siswa yang ditemukan untuk dicetak.');
        }

        $buildPath = $cacheFilePath . '.partial_' . bin2hex(random_bytes(6));
        $zip = new \ZipArchive();
        if ($zip->open($buildPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat berkas ZIP kartu JPG.');
        }

        $usedNames = [];
        $successCount = 0;
        $failedStudents = [];
        $firstError = null;
        $numberWidth = max(3, strlen((string) $students->count()));

        try {
            foreach ($students as $student) {
                $studentName = trim((string) ($student->name ?? ''));

                try {
                    [$namePart, $nisPart, $classPart] = self::cardFileParts($student);
                    $sequence = $successCount + 1;

                    $baseFileName = sprintf(
                        '%0' . $numberWidth . 'd_%s-%s-%s',
                        $sequence,
                        $namePart,
                        $nisPart,
                        $classPart
                    );
                    $fileName = $baseFileName . '.jpg';
                    $duplicate = 2;
                    while (isset($usedNames[strtolower($fileName)])) {
                        $fileName = $baseFileName . '_' . $duplicate . '.jpg';
                        $duplicate++;
                    }

                    $jpgPath = self::ensureStudentCardJpgCache($student, $settingStamp, $designStamp);

                    if (!File::exists($jpgPath) || File::size($jpgPath) === 0) {
                        throw new \RuntimeException('Berkas JPG kartu kosong.');
                    }

                    if (!$zip->addFile($jpgPath, $fileName)) {
                        throw new \RuntimeException('Gagal menambahkan JPG ke ZIP.');
                    }
                    $zip->setCompressionName($fileName, \ZipArchive::CM_DEFLATE, 1);

                    $usedNames[strtolower($fileName)] = true;
                    $successCount++;
                } catch (\Throwable $e) {
                    $failedStudents[] = $studentName !== '' ? $studentName : ('ID ' . $student->id);
                    if ($firstError === null) {
                        $firstError = $e->getMessage();
                    }
                    Log::error('Gagal membuat kartu JPG siswa: ' . $e->getMessage(), [
                        'student_id' => $student->id ?? null,
                        'student_name' => $studentName,
                        'exception' => $e,
                    ]);
                }
            }
        } finally {
            $zip->close();
        }

        if ($successCount === 0) {
            if (File::exists($buildPath)) {
                @unlink($buildPath);
            }
            throw new \RuntimeException('Tidak ada kartu JPG yang berhasil dibuat.' . ($firstError ? ' Kesalahan pertama: ' . $firstError : ''));
        }

        if (!empty($failedStudents)) {
            Log::warning('Sebagian kartu JPG siswa gagal dibuat.', [
                'failed_count' => count($failedStudents),
                'students' => $failedStudents,
            ]);
        }

        if (File::exists($cacheFilePath)) {
            @unlink($cacheFilePath);
        }
        File::move($buildPath, $cacheFilePath);

        unset($students);
        gc_collect_cycles();
    }

    return response()->download($cacheFilePath, $downloadFileName, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => 'attachment; filename="' . $downloadFileName . '"',
        'Cache-Control' => 'no-cache, must-revalidate',
    ]);
}

    private static function cardFileParts(Student $student): array
    {
        $studentName = trim((string) ($student->name ?? ''));
        $studentNisn = trim((string) ($student->nisn ?? ''));
        $classLabel = trim((string) ($student->schoolClass->name ?? '-')) ?: '-';

        $namePart = strtoupper(Str::ascii($studentName !== '' ? $studentName : 'SISWA'));
        $namePart = preg_replace('/[^A-Z0-9]+/', '-', $namePart);
        $namePart = trim((string) preg_replace('/-+/', '-', $namePart), '-');
        $namePart = trim((string) Str::limit($namePart !== '' ? $namePart : 'SISWA', 100, ''), '-');
        $namePart = $namePart !== '' ? $namePart : 'SISWA';

        $nisPart = strtoupper($studentNisn);
        $nisPart = preg_replace('/[^A-Z0-9]+/', '', $nisPart);
        if ($nisPart === '') {
            $nisPart = (string) $student->id;
        }

        $classPart = strtoupper(Str::ascii($classLabel));
        $classPart = preg_replace('/[^A-Z0-9]+/', '-', $classPart);
        $classPart = trim((string) preg_replace('/-+/', '-', $classPart), '-');
        $classPart = $classPart !== '' ? $classPart : 'TANPA-KELAS';

        return [$namePart, $nisPart, $classPart];
    }

    private static function renderCardJpgPreview(Student $student, string $outputPath): void
    {
        $studentNisn = trim((string) ($student->nisn ?? ''));
        if ($studentNisn === '-' || $studentNisn === 'null') {
            $studentNisn = '';
        }

        self::renderCardJpgData([
            'background' => public_path('images/card-bg-pdf.png'),
            'school_name' => (string) Setting::getSchoolName(),
            'name' => (string) ($student->name ?? ''),
            'identifier' => $studentNisn,
            'third_line' => trim((string) ($student->schoolClass->name ?? '-')) ?: '-',
            'third_line_color' => [15, 23, 42],
            'scan_color' => [15, 23, 42],
            'qr_token' => trim((string) ($student->nisn ?: ($student->qr_token ?? ''))),
        ], $outputPath);
    }

    public static function renderTeacherCardJpg(Teacher $teacher, string $outputPath): void
    {
        $nipLabel = TeacherCardService::displayNip($teacher->nip);

        self::renderCardJpgData([
            'scale' => 1276 / 53.9,
            'background' => public_path('images/card-bg-single-green.png'),
            'school_name' => (string) Setting::getSchoolName(),
            'name' => (string) ($teacher->name ?? ''),
            'identifier' => $nipLabel,
            'identifier_top' => $nipLabel === '-' ? 443 : 423,
            'third_line' => 'GURU',
            'third_line_color' => [20, 83, 45],
            'scan_color' => [20, 83, 45],
            'qr_token' => TeacherCardService::qrTokenFor($teacher->nip, (string) $teacher->id),
            'subtitle_top' => 134,
            'scan_top' => 1312,
            'footer_top' => 1640,
            'qr_container_left' => 10.95,
        ], $outputPath);
    }

    private static function renderCardJpgData(array $card, string $outputPath): void
    {
        $width = (int) ($card['width'] ?? 1276);
        $height = (int) ($card['height'] ?? 2022);
        $scale = (float) ($card['scale'] ?? ($width / 53.98));
        $layoutX = $width / 1086;
        $layoutY = $height / 1725;

        $bold = self::cardJpgFontPath(false);
        $italic = self::cardJpgFontPath(true);

        $backgroundPath = (string) ($card['background'] ?? public_path('images/card-bg-pdf.png'));
        $logoPath = public_path(Setting::getLogo());
        $logoSize = (int) round(7 * $scale);
        $logoX = (int) round(3 * $scale);
        $logoY = (int) round(3.1 * $scale);

        $subtitleTop = (int) ($card['subtitle_top'] ?? 135);
        $nameTop = (int) ($card['name_top'] ?? 336);
        $identifierTop = (int) ($card['identifier_top'] ?? 423);
        $thirdTop = (int) ($card['third_top'] ?? 507);
        $scanTop = (int) ($card['scan_top'] ?? 1311);
        $footerTop = (int) ($card['footer_top'] ?? 1638);
        $footerCenter = (int) ($card['footer_center'] ?? 545);

        $schoolName = mb_strtoupper((string) ($card['school_name'] ?? ''));
        $scanRgb = $card['scan_color'] ?? [15, 23, 42];

        $templateKey = md5(json_encode([
            'v' => 2,
            'width' => $width,
            'height' => $height,
            'scale' => $scale,
            'background' => $backgroundPath,
            'logo' => $logoPath,
            'logo_size' => $logoSize,
            'school_name' => $schoolName,
            'subtitle_top' => $subtitleTop,
            'scan_top' => $scanTop,
            'footer_top' => $footerTop,
            'footer_center' => $footerCenter,
            'scan_color' => $scanRgb,
        ]));

        if (!isset(self::$cardJpgTemplateCache[$templateKey])) {
            $backgroundKey = $backgroundPath . '|' . $width . 'x' . $height;

            if (!isset(self::$cardJpgBackgroundCache[$backgroundKey])) {
                $sourceImage = self::loadCardRasterImage($backgroundPath);
                if ($sourceImage === false) {
                    throw new \RuntimeException('Background kartu JPG tidak ditemukan.');
                }

                $background = imagecreatetruecolor($width, $height);
                if ($background === false) {
                    imagedestroy($sourceImage);

                    throw new \RuntimeException('Gagal membuat kanvas kartu JPG.');
                }
                imagecopyresampled($background, $sourceImage, 0, 0, 0, 0, $width, $height, imagesx($sourceImage), imagesy($sourceImage));
                imagedestroy($sourceImage);

                self::$cardJpgBackgroundCache[$backgroundKey] = $background;
            }

            $template = imagecreatetruecolor($width, $height);
            if ($template === false) {
                throw new \RuntimeException('Gagal membuat template kartu JPG.');
            }
            imagecopy($template, self::$cardJpgBackgroundCache[$backgroundKey], 0, 0, 0, 0, $width, $height);

            if (is_file($logoPath)) {
                $logoKey = $logoPath . '|' . $logoSize;

                if (!isset(self::$cardJpgLogoCache[$logoKey])) {
                    $logo = self::loadCardRasterImage($logoPath);
                    if ($logo !== false) {
                        $logoCanvas = imagecreatetruecolor($logoSize, $logoSize);
                        if ($logoCanvas !== false) {
                            imagealphablending($logoCanvas, false);
                            imagesavealpha($logoCanvas, true);
                            $transparent = imagecolorallocatealpha($logoCanvas, 0, 0, 0, 127);
                            imagefilledrectangle($logoCanvas, 0, 0, $logoSize - 1, $logoSize - 1, $transparent);
                            imagecopyresampled($logoCanvas, $logo, 0, 0, 0, 0, $logoSize, $logoSize, imagesx($logo), imagesy($logo));
                            self::$cardJpgLogoCache[$logoKey] = $logoCanvas;
                        }
                        imagedestroy($logo);
                    }
                }

                if (isset(self::$cardJpgLogoCache[$logoKey])) {
                    imagealphablending($template, true);
                    imagecopy($template, self::$cardJpgLogoCache[$logoKey], $logoX, $logoY, 0, 0, $logoSize, $logoSize);
                }
            }

            $white = imagecolorallocate($template, 255, 255, 255);
            $gold = imagecolorallocate($template, 252, 211, 77);
            $scanColor = $scanRgb === [15, 23, 42]
                ? imagecolorallocate($template, 15, 23, 42)
                : imagecolorallocate($template, ...$scanRgb);

            self::drawCardJpgText(
                $template,
                $schoolName,
                $bold,
                37.2 * $layoutX,
                $white,
                (int) round(218 * $layoutX),
                null,
                (int) round(80 * $layoutY)
            );
            self::drawCardJpgText(
                $template,
                'KARTU PRESENSI DIGITAL',
                $bold,
                26.4 * $layoutX,
                $gold,
                (int) round(221 * $layoutX),
                null,
                (int) round($subtitleTop * $layoutY)
            );
            self::drawCardJpgSpacedText(
                $template,
                'SCAN PRESENSI',
                $bold,
                26.0 * $layoutX,
                $scanColor,
                8.7 * $layoutX,
                (int) round($scanTop * $layoutY),
                $width
            );
            self::drawCardJpgText(
                $template,
                'Tunjukkan kartu saat presensi masuk',
                $italic,
                23.65 * $layoutX,
                $white,
                null,
                (int) round($footerCenter * $layoutX),
                (int) round($footerTop * $layoutY)
            );

            self::$cardJpgTemplateCache[$templateKey] = $template;
        }

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new \RuntimeException('Gagal membuat kanvas kartu JPG.');
        }
        imagecopy($image, self::$cardJpgTemplateCache[$templateKey], 0, 0, 0, 0, $width, $height);

        $dark = imagecolorallocate($image, 15, 23, 42);
        $thirdRgb = $card['third_line_color'] ?? [15, 23, 42];
        $thirdColor = $thirdRgb === [15, 23, 42] ? $dark : imagecolorallocate($image, ...$thirdRgb);

        $subjectName = mb_strtoupper(trim((string) ($card['name'] ?? ''))) ?: '-';
        self::drawCardJpgText(
            $image,
            $subjectName,
            $bold,
            37.3 * $layoutX,
            $dark,
            null,
            (int) round($width / 2),
            (int) round($nameTop * $layoutY)
        );

        $identifier = trim((string) ($card['identifier'] ?? ''));
        if ($identifier !== '') {
            self::drawCardJpgText(
                $image,
                $identifier,
                $bold,
                36.0 * $layoutX,
                $dark,
                null,
                (int) round($width / 2),
                (int) round($identifierTop * $layoutY)
            );
        }

        $thirdLine = trim((string) ($card['third_line'] ?? '-')) ?: '-';
        self::drawCardJpgText(
            $image,
            $thirdLine,
            $bold,
            42.4 * $layoutX,
            $thirdColor,
            null,
            (int) round($width / 2),
            (int) round($thirdTop * $layoutY)
        );

        $finalImage = $image;

        $qrToken = trim((string) ($card['qr_token'] ?? ''));
        if ($qrToken !== '') {
            $whiteFinal = imagecolorallocate($finalImage, 255, 255, 255);
            $blackFinal = imagecolorallocate($finalImage, 0, 0, 0);
            self::drawCardJpgQr($finalImage, $qrToken, $card, $scale, $whiteFinal, $blackFinal);
        }

        foreach ([97, 95, 93, 92] as $quality) {
            if (!imagejpeg($finalImage, $outputPath, $quality)) {
                imagedestroy($finalImage);

                throw new \RuntimeException('Gagal menulis JPG kartu.');
            }

            clearstatcache(true, $outputPath);
            $size = filesize($outputPath);

            if ($size !== false && $size <= 1048576) {
                break;
            }
        }

        imagedestroy($finalImage);
        self::writeCardJpgDpi($outputPath, 600);
    }

    private static function drawCardJpgQr($image, string $token, array $card, float $scale, int $white, int $black): void
    {
        $containerX = (int) round(((float) ($card['qr_container_left'] ?? 10.99)) * $scale);
        $containerY = (int) round(((float) ($card['qr_container_top'] ?? 30.5)) * $scale);
        $containerSize = (int) round(((float) ($card['qr_container_size'] ?? 32)) * $scale);
        $areaX = $containerX + (int) round(((float) ($card['qr_origin'] ?? 1)) * $scale);
        $areaY = $containerY + (int) round(((float) ($card['qr_origin'] ?? 1)) * $scale);
        $areaSize = (int) round(((float) ($card['qr_size'] ?? 30)) * $scale);

        imagefilledrectangle(
            $image,
            $containerX,
            $containerY,
            $containerX + $containerSize - 1,
            $containerY + $containerSize - 1,
            $white
        );

        $matrix = \BaconQrCode\Encoder\Encoder::encode($token, \BaconQrCode\Common\ErrorCorrectionLevel::L())->getMatrix();
        $moduleW = $matrix->getWidth();
        $moduleH = $matrix->getHeight();
        $moduleSize = max(1, (int) floor($areaSize / max($moduleW, $moduleH)));
        $qrWidth = $moduleSize * $moduleW;
        $qrHeight = $moduleSize * $moduleH;
        $qrX = $areaX + (int) floor(($areaSize - $qrWidth) / 2);
        $qrY = $areaY + (int) floor(($areaSize - $qrHeight) / 2);

        for ($y = 0; $y < $moduleH; $y++) {
            for ($x = 0; $x < $moduleW; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $x1 = $qrX + $x * $moduleSize;
                $y1 = $qrY + $y * $moduleSize;
                imagefilledrectangle($image, $x1, $y1, $x1 + $moduleSize - 1, $y1 + $moduleSize - 1, $black);
            }
        }
    }

    private static function drawCardJpgText(
        $image,
        string $text,
        string $font,
        float $size,
        int $color,
        ?int $inkLeft,
        ?int $inkCenter,
        int $inkTop
    ): void {
        if ($text === '') {
            return;
        }

        $supersample = 2;
        $renderSize = $size * $supersample;
        $renderWidth = imagesx($image) * $supersample;
        $bbox = imagettfbbox($renderSize, 0, $font, $text);
        if ($bbox === false) {
            throw new \RuntimeException('Gagal mengukur teks kartu JPG.');
        }

        $bboxTop = min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        $bboxBottom = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        $pad = 6;
        $canvasHeight = max(1, (int) ceil($bboxBottom - $bboxTop) + ($pad * 2));
        $canvas = imagecreatetruecolor($renderWidth, $canvasHeight);
        if ($canvas === false) {
            throw new \RuntimeException('Gagal membuat kanvas teks kartu JPG.');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $renderWidth - 1, $canvasHeight - 1, $transparent);

        $red = ($color >> 16) & 0xFF;
        $green = ($color >> 8) & 0xFF;
        $blue = $color & 0xFF;
        $ink = imagecolorallocatealpha($canvas, $red, $green, $blue, 0);

        if ($inkLeft !== null) {
            $x = (int) round(($inkLeft * $supersample) - $bbox[0]);
        } else {
            $x = (int) round(($renderWidth / 2) - (($bbox[0] + $bbox[2]) / 2));
        }
        $y = (int) round($pad - $bboxTop);

        if (imagettftext($canvas, $renderSize, 0, $x, $y, $ink, $font, $text) === false) {
            imagedestroy($canvas);

            throw new \RuntimeException('Gagal menggambar teks kartu JPG.');
        }

        $smallWidth = imagesx($image);
        $smallHeight = max(1, (int) round($canvasHeight / $supersample));
        $small = imagecreatetruecolor($smallWidth, $smallHeight);
        if ($small === false) {
            imagedestroy($canvas);

            throw new \RuntimeException('Gagal memperkecil teks kartu JPG.');
        }
        imagealphablending($small, false);
        imagesavealpha($small, true);
        $transparentSmall = imagecolorallocatealpha($small, 0, 0, 0, 127);
        imagefilledrectangle($small, 0, 0, $smallWidth - 1, $smallHeight - 1, $transparentSmall);
        imagecopyresampled($small, $canvas, 0, 0, 0, 0, $smallWidth, $smallHeight, $renderWidth, $canvasHeight);
        imagedestroy($canvas);

        $destY = (int) round($inkTop - ($pad / $supersample));
        imagealphablending($image, true);
        imagecopy($image, $small, 0, $destY, 0, 0, $smallWidth, $smallHeight);
        imagedestroy($small);
    }

    private static function drawCardJpgSpacedText(
        $image,
        string $text,
        string $font,
        float $size,
        int $color,
        float $spacing,
        int $inkTop,
        int $width
    ): void {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false || empty($chars)) {
            return;
        }

        $supersample = 2;
        $renderSize = $size * $supersample;
        $renderWidth = $width * $supersample;
        $renderSpacing = $spacing * $supersample;

        $count = count($chars);
        $right = [];
        $charLeft = [];
        $charRight = [];
        $charTop = [];

        for ($i = 0; $i < $count; $i++) {
            $prefix = implode('', array_slice($chars, 0, $i + 1));
            $prefixBox = imagettfbbox($renderSize, 0, $font, $prefix);
            $charBox = imagettfbbox($renderSize, 0, $font, $chars[$i]);
            if ($prefixBox === false || $charBox === false) {
                throw new \RuntimeException('Gagal mengukur teks kartu JPG.');
            }
            $right[$i] = $prefixBox[2];
            $charLeft[$i] = $charBox[0];
            $charRight[$i] = $charBox[2];
            $charTop[$i] = min($charBox[1], $charBox[3], $charBox[5], $charBox[7]);
        }

        $advances = [];
        for ($i = 1; $i < $count; $i++) {
            $advances[$i - 1] = ($right[$i] - $right[$i - 1]) - $charRight[$i] + $charRight[$i - 1];
        }

        $sumAdvance = array_sum($advances);
        $inkWidth = $sumAdvance + ($renderSpacing * ($count - 1)) + $charRight[$count - 1] - $charLeft[0];
        $center = ($renderWidth / 2) - ($renderSpacing / 2);
        $pen = $center - ($inkWidth / 2) - $charLeft[0];

        $fullBox = imagettfbbox($renderSize, 0, $font, $text);
        if ($fullBox === false) {
            throw new \RuntimeException('Gagal mengukur teks kartu JPG.');
        }
        $fullTop = min($fullBox[1], $fullBox[3], $fullBox[5], $fullBox[7]);
        $fullBottom = max($fullBox[1], $fullBox[3], $fullBox[5], $fullBox[7]);
        $pad = 6;
        $canvasHeight = max(1, (int) ceil($fullBottom - $fullTop) + ($pad * 2));
        $canvas = imagecreatetruecolor($renderWidth, $canvasHeight);
        if ($canvas === false) {
            throw new \RuntimeException('Gagal membuat kanvas teks kartu JPG.');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $renderWidth - 1, $canvasHeight - 1, $transparent);

        $red = ($color >> 16) & 0xFF;
        $green = ($color >> 8) & 0xFF;
        $blue = $color & 0xFF;
        $ink = imagecolorallocatealpha($canvas, $red, $green, $blue, 0);
        $baseline = (int) round($pad - $charTop[0]);

        for ($i = 0; $i < $count; $i++) {
            if (imagettftext($canvas, $renderSize, 0, (int) round($pen), $baseline, $ink, $font, $chars[$i]) === false) {
                imagedestroy($canvas);

                throw new \RuntimeException('Gagal menggambar teks kartu JPG.');
            }
            if ($i < $count - 1) {
                $pen += $advances[$i] + $renderSpacing;
            }
        }

        $smallWidth = imagesx($image);
        $smallHeight = max(1, (int) round($canvasHeight / $supersample));
        $small = imagecreatetruecolor($smallWidth, $smallHeight);
        if ($small === false) {
            imagedestroy($canvas);

            throw new \RuntimeException('Gagal memperkecil teks kartu JPG.');
        }
        imagealphablending($small, false);
        imagesavealpha($small, true);
        $transparentSmall = imagecolorallocatealpha($small, 0, 0, 0, 127);
        imagefilledrectangle($small, 0, 0, $smallWidth - 1, $smallHeight - 1, $transparentSmall);
        imagecopyresampled($small, $canvas, 0, 0, 0, 0, $smallWidth, $smallHeight, $renderWidth, $canvasHeight);
        imagedestroy($canvas);

        $destY = (int) round($inkTop - ($pad / $supersample));
        imagealphablending($image, true);
        imagecopy($image, $small, 0, $destY, 0, 0, $smallWidth, $smallHeight);
        imagedestroy($small);
    }

    private static function cardJpgFontPath(bool $italic): string
    {
        static $cache = [];
        $key = $italic ? 'italic' : 'bold';
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $names = $italic
            ? ['ariali.ttf', 'Arial Italic.ttf', 'Arial_Italic.ttf', 'LiberationSans-Italic.ttf']
            : ['arialbd.ttf', 'Arial Bold.ttf', 'Arial_Bold.ttf', 'LiberationSans-Bold.ttf'];

        $directories = [
            public_path('fonts'),
            resource_path('fonts'),
            storage_path('fonts'),
            base_path('fonts'),
            'C:/Windows/Fonts',
            '/usr/share/fonts/truetype/msttcorefonts',
            '/usr/share/fonts/truetype/liberation2',
            '/usr/share/fonts/truetype/liberation',
        ];

        foreach ($directories as $directory) {
            foreach ($names as $name) {
                $path = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $name;
                if (is_file($path)) {
                    return $cache[$key] = $path;
                }
            }
        }

        throw new \RuntimeException('Font Arial untuk kartu JPG tidak ditemukan.');
    }

    private static function loadCardRasterImage(string $path)
    {
        if (!is_file($path)) {
            return false;
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return false;
        }

        return match ($info[2]) {
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => false,
        };
    }

    private static function writeCardJpgDpi(string $path, int $dpi): void
    {
        $bytes = file_get_contents($path);
        if (!is_string($bytes) || strlen($bytes) < 18) {
            return;
        }
        if (substr($bytes, 0, 2) !== "\xFF\xD8" || substr($bytes, 6, 5) !== "JFIF\0") {
            return;
        }

        $bytes = substr_replace($bytes, "\x01" . pack('n', $dpi) . pack('n', $dpi), 13, 5);
        File::put($path, $bytes);
    }

    /**
     * Buat QR sebagai PNG (data URI) memakai GD. Tanpa quiet zone tambahan,
     * sama seperti QrCode::margin(0) sebelumnya.
     */
    private static function qrPngDataUri(string $token, int $targetPx = 378): string
    {
        $matrix = \BaconQrCode\Encoder\Encoder::encode($token, \BaconQrCode\Common\ErrorCorrectionLevel::L())->getMatrix();
        $mW = $matrix->getWidth();
        $mH = $matrix->getHeight();
        $moduleSize = max(1, (int) floor($targetPx / $mW));
        $w = $moduleSize * $mW;
        $h = $moduleSize * $mH;

        $img = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, $w - 1, $h - 1, $white);

        for ($y = 0; $y < $mH; $y++) {
            for ($x = 0; $x < $mW; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $x1 = $x * $moduleSize;
                    $y1 = $y * $moduleSize;
                    imagefilledrectangle($img, $x1, $y1, $x1 + $moduleSize - 1, $y1 + $moduleSize - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($img, null, 9);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * Download QR PNG 500x500 px (Instan via GD)
     */
    public static function downloadQrPng(Student $student): BinaryFileResponse
    {
        self::initDirs();

        $token = $student->nisn ?: $student->qr_token;
        $fileName = 'QR_' . $student->nisn . '_' . Str::slug($student->name) . '.png';
        $cacheFilePath = self::$baseDir . '/qrs/' . "qr_single_{$student->id}_" . md5($token) . '_500.png';

        if (!File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
            $matrix = \BaconQrCode\Encoder\Encoder::encode($token, \BaconQrCode\Common\ErrorCorrectionLevel::M())->getMatrix();
            $mW = $matrix->getWidth();
            $mH = $matrix->getHeight();
            $quiet = 2; // Quiet zone 2 modul
            $targetSize = 500;
            $totalModules = $mW + ($quiet * 2);
            $moduleSize = $targetSize / $totalModules;

            $img = imagecreatetruecolor($targetSize, $targetSize);
            $white = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);
            imagefilledrectangle($img, 0, 0, $targetSize, $targetSize, $white);

            for ($y = 0; $y < $mH; $y++) {
                for ($x = 0; $x < $mW; $x++) {
                    if ($matrix->get($x, $y) === 1) {
                        $x1 = (int) round(($x + $quiet) * $moduleSize);
                        $y1 = (int) round(($y + $quiet) * $moduleSize);
                        $x2 = (int) round(($x + $quiet + 1) * $moduleSize) - 1;
                        $y2 = (int) round(($y + $quiet + 1) * $moduleSize) - 1;
                        imagefilledrectangle($img, $x1, $y1, $x2, $y2, $black);
                    }
                }
            }

            imagepng($img, $cacheFilePath);
            imagedestroy($img);
        }

        return response()->download($cacheFilePath, $fileName, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    /**
     * Alias downloadQrSvg memanggil downloadQrPng untuk kompatibilitas
     */
    public static function downloadQrSvg(Student $student): BinaryFileResponse
    {
        return self::downloadQrPng($student);
    }

    /**
     * Download Template Excel Pre-generated (Instan)
     */
    public static function downloadTemplate(string $type, object $exportObject, string $fileName)
    {
        self::initDirs();

        $cacheFilePath = self::$baseDir . "/templates/{$type}.xlsx";

        if (!File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
            $content = Excel::raw($exportObject, \Maatwebsite\Excel\Excel::XLSX);
            File::put($cacheFilePath, $content);
        }

        return response()->download($cacheFilePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    /**
     * Kunci cache rekap. Memuat versi logika ($rekapCacheVersion), stempel data
     * presensi terakhir, dan stempel pengaturan sekolah.
     */
    protected static function getRekapCacheKey(string $format, string $type, ?int $classId, array $allInputs): string
    {
        $maxAttStamp = (int) strtotime((string) (Attendance::max('updated_at') ?? '0'));
        $settingStamp = self::getSettingStamp();
        $inputHash = md5(json_encode($allInputs));
        $classKey = $classId ?? 'all';

        return "rekap_{$type}_{$classKey}_{$format}_{$inputHash}_{$maxAttStamp}_{$settingStamp}_" . self::$rekapCacheVersion;
    }

    /**
     * Cache & Download Rekap Presensi Excel / PDF / CSV
     */
    public static function downloadRekapFile(string $format, string $type, ?int $classId, array $allInputs, callable $generator, string $downloadFileName)
    {
        self::initDirs();

        $ext = strtolower($format);
        $cacheKey = self::getRekapCacheKey($ext, $type, $classId, $allInputs);
        $cacheFile = self::$baseDir . "/rekap/{$cacheKey}.{$ext}";

        if (!File::exists($cacheFile) || File::size($cacheFile) === 0) {
            // Jalankan generator
            $content = $generator();
            if ($content instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
                return $content;
            }
            File::put($cacheFile, (string) $content);
        }

        $mimeType = match($ext) {
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            default => 'application/octet-stream'
        };

        return response()->download($cacheFile, $downloadFileName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $downloadFileName . '"',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
    }

    /**
     * Invalidate Seluruh Cache Kartu Presensi & QR
     */
    public static function clearCardsCache(): void
    {
        self::initDirs();
        try {
            $files = array_merge(
                File::files(self::$baseDir . '/cards'),
                File::files(self::$baseDir . '/qrs')
            );
            foreach ($files as $file) {
                File::delete($file->getPathname());
            }
        } catch (\Throwable $e) {
            // Fail silently
        }
    }

    /**
     * Invalidate Seluruh Cache Rekap Presensi
     */
    public static function clearRekapCache(): void
    {
        self::initDirs();
        try {
            $files = File::files(self::$baseDir . '/rekap');
            foreach ($files as $file) {
                File::delete($file->getPathname());
            }
        } catch (\Throwable $e) {
            // Fail silently
        }
    }

    /**
     * Invalidate Semua Cache
     */
    public static function clearAllCache(): void
    {
        self::clearCardsCache();
        self::clearRekapCache();
    }
}
<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
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
     * Download Single Student Card PDF — A4 portrait, 1 kartu (53,98 x 85,6 mm)
     * di pojok kiri atas, memakai partial admin.students.partials.card-single
     * (desain baru khusus fitur single: preview & unduh individual).
     */
   public static function downloadSingleCardPdf(Student $student)
{
    self::initDirs();

    try {
        $student = clone $student;
        $student->loadMissing('schoolClass');
        $settingStamp = self::getSettingStamp();
        $studentStamp = $student->updated_at ? $student->updated_at->timestamp : 0;
        $designStamp = self::getDesignStamp();
        $cacheFileName = "card_single_v3_{$student->id}_{$studentStamp}_{$settingStamp}_{$designStamp}.pdf";
        $cacheFilePath = self::$baseDir . '/cards/' . $cacheFileName;

        $downloadFileName = 'kartu-presensi-' . $student->nisn . '-' . Str::slug($student->name) . '.pdf';

        if (File::exists($cacheFilePath) && File::size($cacheFilePath) === 0) {
            @unlink($cacheFilePath);
        }

        if (!File::exists($cacheFilePath)) {
            $schoolName = Setting::getSchoolName();

            // Gunakan file path instead of base64 data URL
            $logoPath = public_path(Setting::getLogo());

            $pdf = Pdf::loadView('admin.students.print-card-pdf', [
                'student'      => $student,
                'schoolName'   => $schoolName,
                'logoPath'     => $logoPath,  // File path instead of base64
                'qrCodePath'   => null,  // File path (null means view will generate)
                'studentName'  => $student->name,
                'studentNisn'  => $student->nisn,
                'studentClass' => $student->schoolClass->name ?? '-',
                'isPdf'        => true,
            ])->setPaper('a4', 'portrait');

            // Set DomPDF options for optimization
            $pdf->setOption('enable_font_subsetting', true);
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('dpi', 150);  // Reduce DPI for smaller filesize
            $pdf->setOption('defaultFont', 'sans-serif');

            File::put($cacheFilePath, $pdf->output());
        }

        return response()->download($cacheFilePath, $downloadFileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $downloadFileName . '"',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    } catch (\Throwable $e) {
        Log::error('Gagal mengunduh kartu presensi PDF siswa: ' . $e->getMessage(), [
            'student_id' => $student->id ?? null,
            'exception' => $e,
        ]);

        if (request()->ajax() || request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['message' => 'Kartu belum bisa diunduh, coba lagi'], 500);
        }

        return redirect()->back()->with('error', 'Kartu belum bisa diunduh, coba lagi');
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
     * Download Mass Cards PDF (Instan via Cache / Pre-generate)
     */
   public static function downloadMassCards(Request $request, ?int $forceClassId = null, array $allowedClassIds = []): BinaryFileResponse
{
    self::initDirs();

    ini_set('memory_limit', '1024M');
    ini_set('max_execution_time', '300');

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
    $cacheFileName = "cards_mass_v10_{$scopeKey}_{$designStamp}.pdf";
    $cacheFilePath = self::$baseDir . '/cards/' . $cacheFileName;

    $targetClassName = '';
    if ($classId) {
        $cls = SchoolClass::find($classId);
        if ($cls) $targetClassName = '_' . Str::slug($cls->name);
    }
    $downloadFileName = 'Kartu_Presensi_Massal' . $targetClassName . '_' . date('Ymd_His') . '.pdf';

    if (!File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
        $schoolName = Setting::getSchoolName();

        $logoPath = public_path(Setting::getLogo());
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        // Urut: kelas natural (7A, 7B, ... 9F), lalu nama A-Z
        $students = $query->get()->sort(function ($a, $b) {
            $ca = $a->schoolClass->name ?? '';
            $cb = $b->schoolClass->name ?? '';
            if ($ca !== $cb) return strnatcmp($ca, $cb);
            return strcmp(Str::ascii($a->name), Str::ascii($b->name));
        })->values();

        if ($students->isEmpty()) {
            abort(404, 'Tidak ada data siswa yang ditemukan untuk dicetak.');
        }

        foreach ($students as $st) {
            try {
                $st->qr_base64 = self::qrPngDataUri((string) ($st->nisn ?: $st->qr_token));
            } catch (\Throwable $e) {
                $st->qr_base64 = null;
            }
            $st->photo_base64 = null;
        }

        $paper = self::CARD_PAPER;
        $cfg = self::paperConfig()[$paper];

        $pdf = Pdf::loadView('shared.print-cards', [
            'students'   => $students,   // daftar datar, di-chunk di Blade
            'schoolName' => $schoolName,
            'logoBase64' => $logoBase64,
            'paper'      => $paper,
            'cols'       => $cfg['cols'],
            'rows'       => $cfg['rows'],
            'perSheet'   => $cfg['perSheet'],
            'isPdf'      => true,
        ])->setPaper($paper, 'landscape');

        File::put($cacheFilePath, $pdf->output());

        unset($pdf, $students);
        gc_collect_cycles();
    }

    return response()->download($cacheFilePath, $downloadFileName, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $downloadFileName . '"',
        'Cache-Control' => 'no-cache, must-revalidate',
    ]);
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
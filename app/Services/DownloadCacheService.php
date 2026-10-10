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
            $cacheFileName = "card_single_{$student->id}_{$studentStamp}_{$settingStamp}.png";
            $cacheFilePath = self::$baseDir . '/cards/' . $cacheFileName;

            $downloadFileName = 'Kartu_Presensi_' . $student->nisn . '_' . Str::slug($student->name) . '.png';

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
     * Render Single Student Card PNG menggunakan PHP GD murni.
     * Standar CR80 Portrait: 1086 x 1725 px (~500 DPI), sudut lengkung 64 px (~3.18mm),
     * latar transparan di luar sudut, desain identik dengan card.blade.php.
     */
    public static function renderCardPngWithGd(Student $student, string $outputPath): void
    {
        $w = 1086;
        $h = 1725;
        $radius = 64;

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

        // Proporsi tinggi elemen (total 1725 px)
        $headerH = 226; // 11.2mm
        $accentH = 16;  // 0.8mm
        $footerH = 134; // 6.6mm
        $footerY = $h - $footerH; // 1591

        // 1. Gambar badan kartu putih
        imagefilledrectangle($img, 0, $headerH + $accentH, $w - 1, $footerY - 1, $white);

        // 2. Garis aksen oranye
        imagefilledrectangle($img, 0, $headerH, $w - 1, $headerH + $accentH - 1, $amber);

        // 3. Header Navy dengan sudut atas melengkung
        imagefilledrectangle($img, 0, $radius, $w - 1, $headerH - 1, $navy);
        imagefilledrectangle($img, $radius, 0, $w - 1 - $radius, $radius, $navy);
        imagefilledellipse($img, $radius, $radius, $radius * 2, $radius * 2, $navy);
        imagefilledellipse($img, $w - 1 - $radius, $radius, $radius * 2, $radius * 2, $navy);

        // 4. Footer Abu-abu dengan sudut bawah melengkung
        imagefilledrectangle($img, 0, $footerY, $w - 1, $h - 1 - $radius, $grayLight);
        imagefilledrectangle($img, $radius, $h - 1 - $radius, $w - 1 - $radius, $h - 1, $grayLight);
        imagefilledellipse($img, $radius, $h - 1 - $radius, $radius * 2, $radius * 2, $grayLight);
        imagefilledellipse($img, $w - 1 - $radius, $h - 1 - $radius, $radius * 2, $radius * 2, $grayLight);
        imageline($img, 0, $footerY, $w - 1, $footerY, $grayBorder);

        // 5. Potong sudut luar kartu agar transparan sempurna
        for ($y = 0; $y < $radius; $y++) {
            for ($x = 0; $x < $radius; $x++) {
                $dx = $radius - $x;
                $dy = $radius - $y;
                if (($dx * $dx + $dy * $dy) > ($radius * $radius)) {
                    imagesetpixel($img, $x, $y, $transparent);
                    imagesetpixel($img, $w - 1 - $x, $y, $transparent);
                }
            }
        }
        for ($y = 0; $y < $radius; $y++) {
            for ($x = 0; $x < $radius; $x++) {
                $dx = $radius - $x;
                $dy = $y;
                if (($dx * $dx + $dy * $dy) > ($radius * $radius)) {
                    imagesetpixel($img, $x, $h - 1 - $radius + $y, $transparent);
                    imagesetpixel($img, $w - 1 - $x, $h - 1 - $radius + $y, $transparent);
                }
            }
        }

        // 6. Muat font TTF bawaan vendor/dompdf (tersedia di lokal dan production)
        $fontBold = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');
        $fontOblique = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Oblique.ttf');

        // 7. Render Header: Logo Sekolah + Nama Sekolah + KARTU PRESENSI DIGITAL
        $logoPath = public_path(Setting::getLogo());
        $logoSize = 160;
        $logoX = 45;
        $logoY = (int) round(($headerH - $logoSize) / 2);
        $textStartX = $logoX;

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
                    $textStartX = $logoX + $logoSize + 30;
                }
            }
        }

        $schoolName = mb_strtoupper(Setting::getSchoolName() ?: 'SMP PGRI PARUNGPANJANG');
        $schoolFontSize = 32;
        while ($schoolFontSize > 20) {
            $bbox = imagettfbbox($schoolFontSize, 0, $fontBold, $schoolName);
            $textW = abs($bbox[4] - $bbox[0]);
            if ($textStartX + $textW < ($w - 30)) {
                break;
            }
            $schoolFontSize -= 2;
        }
        imagettftext($img, $schoolFontSize, 0, $textStartX, 105, $white, $fontBold, $schoolName);
        imagettftext($img, 24, 0, $textStartX, 160, $gold, $fontBold, 'KARTU PRESENSI DIGITAL');

        // 8. Render Nama Siswa (UKURAN TETAP, maksimal 2 baris, vertikal tengah, Bold)
        $studentName = mb_strtoupper(trim($student->name));
        $maxNameWidth = $w - 120; // Margin kiri-kanan 60 px

        $wrapLines = function(string $text, int $size, string $font, int $maxWidth): array {
            $words = explode(' ', $text);
            $lines = [];
            $curLine = '';
            foreach ($words as $word) {
                $testLine = $curLine === '' ? $word : $curLine . ' ' . $word;
                $bbox = imagettfbbox($size, 0, $font, $testLine);
                $lineW = abs($bbox[4] - $bbox[0]);
                if ($lineW > $maxWidth && $curLine !== '') {
                    $lines[] = $curLine;
                    $curLine = $word;
                } else {
                    $curLine = $testLine;
                }
            }
            if ($curLine !== '') {
                $lines[] = $curLine;
            }
            return $lines;
        };

        // UKURAN TETAP — sama untuk semua kartu (nama 37px ≈ 7pt kartu PDF).
        // Nama tampil SATU BARIS; nama terpanjang tetap muat karena ukuran sudah
        // diturunkan untuk SEMUA kartu (bukan per kartu).
        $nameFontSize = 37;
        $lineStep = (int) round($nameFontSize * 1.2);   // tinggi baris rapat (line-height 1.2)
        $nameBaseline = 336;                             // posisi tetap (nama satu baris)

        $lines = $wrapLines($studentName, $nameFontSize, $fontBold, $maxNameWidth);
        $nameText = count($lines) === 1 ? $lines[0] : implode(' ', $lines); // selalu 1 baris

        $bbox = imagettfbbox($nameFontSize, 0, $fontBold, $nameText);
        $lineW = abs($bbox[4] - $bbox[0]);
        $lx = (int) round(($w - $lineW) / 2);
        imagettftext($img, $nameFontSize, 0, $lx, $nameBaseline, $dark, $fontBold, $nameText);

        // 9. Render NISN Siswa (ukuran tetap ~85% nama, bold & warna sama dengan nama/kelas)
        $studentNisn = trim((string) ($student->nisn ?? ''));
        $nisnFontSize = 36;                       // tetap (≈6,8pt)
        $nisnBaseline = $nameBaseline + 46;       // posisi tetap, jarak rapat antar baris

        if ($studentNisn !== '') {
            $nisnColor = imagecolorallocate($img, 15, 23, 42);  // #0f172a (sama dengan nama/kelas)
            $nisnBbox = imagettfbbox($nisnFontSize, 0, $fontBold, $studentNisn);
            $nisnW = abs($nisnBbox[4] - $nisnBbox[0]);
            $nisnX = (int) round(($w - $nisnW) / 2);
            imagettftext($img, $nisnFontSize, 0, $nisnX, $nisnBaseline, $nisnColor, $fontBold, $studentNisn);
        }

        // 10. Render Kelas Siswa (posisi TETAP di bawah NISN)
        $className = mb_strtoupper($student->schoolClass->name ?? ($student->kelas ?? '-'));
        $classFontSize = 42;   // tetap (≈8pt)
        $classBbox = imagettfbbox($classFontSize, 0, $fontBold, $className);
        $classW = abs($classBbox[4] - $classBbox[0]);
        $classX = (int) round(($w - $classW) / 2);
        $classY = $nisnBaseline + 48;
        imagettftext($img, $classFontSize, 0, $classX, $classY, $dark, $fontBold, $className);

        // 11. Render QR Code (Matrix BaconQrCode, Tajam & Presisi)
        $token = $student->nisn ?: $student->qr_token;
        $qrMatrix = \BaconQrCode\Encoder\Encoder::encode($token, \BaconQrCode\Common\ErrorCorrectionLevel::M())->getMatrix();
        $mW = $qrMatrix->getWidth();
        $mH = $qrMatrix->getHeight();

        $qrTargetSize = 680;
        $moduleSize = (int) floor($qrTargetSize / $mW);
        $qrActualSize = $moduleSize * $mW;
        $qrStartX = (int) round(($w - $qrActualSize) / 2);
        $qrStartY = 640;

        // Background putih quiet zone
        imagefilledrectangle($img, $qrStartX - 15, $qrStartY - 15, $qrStartX + $qrActualSize + 14, $qrStartY + $qrActualSize + 14, $white);

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

        // 12. Render Teks "SCAN PRESENSI"
        $scanBbox = imagettfbbox(28, 0, $fontBold, 'SCAN PRESENSI');
        $scanW = abs($scanBbox[4] - $scanBbox[0]);
        $scanX = (int) round(($w - $scanW) / 2);
        $scanY = $qrStartY + $qrActualSize + 65;
        imagettftext($img, 28, 0, $scanX, $scanY, $dark, $fontBold, 'SCAN PRESENSI');

        // 13. Render Footer Text
        $footerText = 'Tunjukkan kartu saat presensi masuk';
        $footBbox = imagettfbbox(24, 0, $fontOblique, $footerText);
        $footW = abs($footBbox[4] - $footBbox[0]);
        $footX = (int) round(($w - $footW) / 2);
        $footY = $footerY + 78;
        imagettftext($img, 24, 0, $footX, $footY, $grayText, $fontOblique, $footerText);

        // 14. Tulis file PNG dengan kompresi maksimal (9)
        imagepng($img, $outputPath, 9);
        imagedestroy($img);
    }

    /**
     * Download Mass Cards PDF (Instan via Cache / Pre-generate)
     */
    public static function downloadMassCards(Request $request, ?int $forceClassId = null, array $allowedClassIds = []): BinaryFileResponse
    {
        self::initDirs();

        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');

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

        // Hitung identifikasi versi data untuk cache
        $studentCount = (clone $query)->count();
        $latestUpdate = (clone $query)->max('updated_at') ?? '0';
        $settingStamp = self::getSettingStamp();

        $idsHash = $studentIds ? md5(implode(',', $studentIds)) : 'all';
        $scopeKey = ($classId ? "cls_{$classId}" : 'all') . "_{$idsHash}_{$studentCount}_" . strtotime($latestUpdate) . "_{$settingStamp}";
        $cacheFileName = "cards_mass_{$scopeKey}.pdf";
        $cacheFilePath = self::$baseDir . '/cards/' . $cacheFileName;

        $targetClassName = '';
        if ($classId) {
            $cls = SchoolClass::find($classId);
            if ($cls) $targetClassName = '_' . Str::slug($cls->name);
        }
        $downloadFileName = 'Kartu_Presensi_Massal' . $targetClassName . '_' . date('Ymd_His') . '.pdf';

        if (!File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
            $activeYear = AcademicYear::getActive();
            $schoolName = Setting::getSchoolName();
            $schoolAddress = Setting::getSchoolAddress();

            $logoPath = public_path(Setting::getLogo());
            $logoBase64 = null;
            if (file_exists($logoPath)) {
                $mime = mime_content_type($logoPath) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            }

            $students = collect();
            $query->orderBy('school_class_id')->orderBy('name')->chunk(100, function ($chunk) use ($students) {
                foreach ($chunk as $st) {
                    $token = $st->nisn ?: $st->qr_token;
                    try {
                        $svg = QrCode::size(140)->margin(0)->generate($token);
                        $st->qr_base64 = 'data:image/svg+xml;base64,' . base64_encode($svg);
                    } catch (\Throwable $e) {
                        $st->qr_base64 = null;
                    }
                    $st->photo_base64 = null;
                    $students->push($st);
                }
            });

            if ($students->isEmpty()) {
                abort(404, 'Tidak ada data siswa yang ditemukan untuk dicetak.');
            }

            $pdf = Pdf::loadView('shared.print-cards', array_merge(compact(
                'students',
                'schoolName',
                'schoolAddress',
                'activeYear',
                'logoBase64'
            ), ['isPdf' => true]))->setPaper('a4', 'landscape');

            File::put($cacheFilePath, $pdf->output());
        }

        return response()->download($cacheFilePath, $downloadFileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $downloadFileName . '"',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
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
     * Cache & Download Rekap Presensi Excel / PDF / CSV
     */
    public static function downloadRekapFile(string $format, string $type, ?int $classId, array $allInputs, callable $generator, string $downloadFileName)
    {
        self::initDirs();

        // Identifikasi versi data presensi
        $maxAttStamp = strtotime((string) (Attendance::max('updated_at') ?? '0'));
        $settingStamp = self::getSettingStamp();
        $inputHash = md5(json_encode($allInputs));
        $classKey = $classId ?? 'all';

        $ext = strtolower($format);
        $cacheFile = self::$baseDir . "/rekap/rekap_{$type}_{$classKey}_{$ext}_{$inputHash}_{$maxAttStamp}_{$settingStamp}.{$ext}";

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

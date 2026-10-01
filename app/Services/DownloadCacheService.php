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
    public static function downloadSingleCard(Student $student): BinaryFileResponse
    {
        self::initDirs();

        $student = clone $student;
        $student->loadMissing('schoolClass');
        $settingStamp = self::getSettingStamp();
        $studentStamp = $student->updated_at ? $student->updated_at->timestamp : 0;
        $cacheFileName = "card_single_{$student->id}_{$studentStamp}_{$settingStamp}.png";
        $cacheFilePath = self::$baseDir . '/cards/' . $cacheFileName;

        $downloadFileName = 'Kartu_Presensi_' . $student->nis . '_' . Str::slug($student->name) . '.png';

        if (!File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
            $schoolName = Setting::getSchoolName();
            $logoPath = public_path(Setting::getLogo());
            $logoBase64 = null;
            if (file_exists($logoPath)) {
                $mime = mime_content_type($logoPath) ?: 'image/png';
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            }

            $token = $student->qr_token ?? $student->nis;
            try {
                $svg = QrCode::size(140)->margin(0)->generate($token);
                $student->qr_base64 = 'data:image/svg+xml;base64,' . base64_encode($svg);
            } catch (\Throwable $e) {
                $student->qr_base64 = null;
            }

            // Render komponen bersama card.blade.php (Single Source of Truth)
            $cardHtml = view('admin.students.partials.card', [
                'studentItem' => $student,
                'logoSrc' => $logoBase64,
                'schoolName' => $schoolName,
                'studentName' => $student->name,
                'studentClass' => $student->schoolClass->name ?? '-',
            ])->render();

            $fullHtml = '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    -webkit-font-smoothing: antialiased;
  }
  html, body {
    margin: 0;
    padding: 0;
    width: 53.98mm;
    height: 85.6mm;
    background: transparent !important;
    overflow: hidden;
  }
  .presensi-card-table {
    box-shadow: none !important;
  }
</style>
</head>
<body>
' . $cardHtml . '
</body>
</html>';

            $tempHtmlPath = self::$baseDir . "/cards/temp_{$student->id}_" . uniqid() . '.html';
            File::put($tempHtmlPath, $fullHtml);

            $browser = self::getBrowserBinary();
            if ($browser) {
                // Window size 204,324 dengan scale factor 5.3235 menghasilkan 1086 x 1725 px (CR80 ~500 DPI)
                $cmd = sprintf(
                    '"%s" --headless=new --disable-gpu --no-sandbox --default-background-color=00000000 --hide-scrollbars --window-size=204,324 --force-device-scale-factor=5.3235 --screenshot="%s" "file:///%s"',
                    $browser,
                    $cacheFilePath,
                    str_replace('\\', '/', $tempHtmlPath)
                );
                exec($cmd);
            }

            @unlink($tempHtmlPath);
        }

        return response()->download($cacheFilePath, $downloadFileName, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $downloadFileName . '"',
            'Cache-Control' => 'no-cache, must-revalidate'
        ]);
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
                    $token = $st->qr_token ?? $st->nis;
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

        $token = $student->qr_token ?? $student->nis;
        $fileName = 'QR_' . $student->nis . '_' . Str::slug($student->name) . '.png';
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

<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Teacher;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeacherCardService
{
    private const PAPER = 'a4';

    private const CARD_WIDTH_MM = 53.9;

    private const CARD_HEIGHT_MM = 85.6;

    private const CARD_GAP_MM = 3;

    private const CARD_COLUMNS = 5;

    private const CARD_ROWS = 2;

    private const CARDS_PER_SHEET = 10;

    private static string $cacheDir = '';

    /**
     * Guru aktif yang kartunya akan dicetak, urut nama A-Z.
     */
    public static function activeTeachers(): Collection
    {
        return Teacher::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'Aktif');
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Teacher $teacher) => self::prepareTeacher($teacher))
            ->values();
    }

    /**
     * NIP yang boleh dipakai sebagai isi QR. Nilai kosong, "-", "null",
     * atau NIP yang tidak berbentuk 16/18 digit tidak pernah dipakai.
     */
    public static function normalizeNip(?string $nip): ?string
    {
        $nip = preg_replace('/\s+/', '', trim((string) $nip));

        if ($nip === '' || $nip === '-' || strcasecmp($nip, 'null') === 0) {
            return null;
        }

        return preg_match('/^(?:\d{16}|\d{18})$/', $nip) === 1 ? $nip : null;
    }

    /**
     * Token QR guru: NIP valid bila tersedia; jika tidak, pakai qr_token guru
     * (identifier yang dipakai sistem) agar setiap guru tanpa NIP tetap punya
     * QR unik, bukan "-" yang sama untuk semua orang.
     */
    public static function qrTokenFor(?string $nip, ?string $qrToken = null): string
    {
        $normalizedNip = self::normalizeNip($nip);

        if ($normalizedNip !== null) {
            return $normalizedNip;
        }

        $qrToken = trim((string) $qrToken);

        return $qrToken !== '' ? $qrToken : '-';
    }

    /**
     * Teks NIP yang ditampilkan pada kartu.
     */
    public static function displayNip(?string $nip): string
    {
        $nip = trim((string) $nip);

        if ($nip === '' || $nip === '-' || strcasecmp($nip, 'null') === 0) {
            return '-';
        }

        return $nip;
    }

    /**
     * Preview HTML kartu guru massal (GET), memakai desain yang sama dengan PDF.
     */
    public static function preview(): View
    {
        $teachers = self::activeTeachers();

        if ($teachers->isEmpty()) {
            abort(404, __('Tidak ada data guru aktif yang ditemukan untuk dicetak.'));
        }

        return view('admin.teachers.partials.mass-card-pdf', [
            'teachers' => $teachers,
            'schoolName' => Setting::getSchoolName(),
            'logoBase64' => self::logoDataUri(),
        ]);
    }

    /**
     * Generate / ambil dari cache lalu unduh PDF kartu guru massal.
     */
    public static function downloadMassCards(): BinaryFileResponse
    {
        $teachers = self::activeTeachers();

        if ($teachers->isEmpty()) {
            abort(404, __('Tidak ada data guru aktif yang ditemukan untuk dicetak.'));
        }

        self::initCacheDir();

        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '300');

        $cacheFilePath = self::$cacheDir.'/'.self::cacheKey($teachers);

        if (! File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
            $pdf = Pdf::loadView('admin.teachers.partials.mass-card-pdf', [
                'teachers' => $teachers,
                'schoolName' => Setting::getSchoolName(),
                'logoBase64' => self::logoDataUri(),
            ])->setPaper(self::PAPER, 'landscape');

            $pdf->setOption('enable_font_subsetting', true);
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('dpi', 150);
            $pdf->setOption('defaultFont', 'sans-serif');

            File::put($cacheFilePath, $pdf->output());

            if (! File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
                throw new \RuntimeException('Berkas kartu presensi guru gagal dibuat atau berukuran 0 byte.');
            }

            unset($pdf, $teachers);
            gc_collect_cycles();
        }

        $downloadFileName = 'Kartu_Presensi_Guru_Massal_'.date('Ymd_His').'.pdf';

        return response()->download($cacheFilePath, $downloadFileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$downloadFileName.'"',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Unduh PDF kartu presensi satu guru (A4 portrait, 1 kartu).
     */
    public static function downloadSingleCardPdf(Teacher $teacher)
    {
        try {
            $teacher = clone $teacher;
            $teacher->loadMissing('schoolClass');
            self::initCacheDir();

            $cacheFilePath = self::$cacheDir.'/'.self::singleCardCacheKey($teacher);

            if (! File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
                $pdf = Pdf::loadView('admin.teachers.print-card-pdf', [
                    'teacher' => $teacher,
                    'schoolName' => Setting::getSchoolName(),
                    'isPdf' => true,
                ])->setPaper(self::PAPER, 'portrait');

                $pdf->setOption('enable_font_subsetting', true);
                $pdf->setOption('isHtml5ParserEnabled', true);
                $pdf->setOption('dpi', 150);
                $pdf->setOption('defaultFont', 'sans-serif');

                File::put($cacheFilePath, $pdf->output());

                if (! File::exists($cacheFilePath) || File::size($cacheFilePath) === 0) {
                    throw new \RuntimeException('Berkas kartu presensi guru gagal dibuat atau berukuran 0 byte.');
                }
            }

            $identifier = self::normalizeNip($teacher->nip) ?: ($teacher->qr_token ?: (string) $teacher->id);
            $downloadFileName = 'kartu-presensi-guru-'.Str::slug($identifier).'-'.Str::slug($teacher->name).'.pdf';

            return response()->download($cacheFilePath, $downloadFileName, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$downloadFileName.'"',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal mengunduh kartu presensi guru: '.$e->getMessage(), [
                'teacher_id' => $teacher->id ?? null,
                'exception' => $e,
            ]);

            if (request()->ajax() || request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'message' => __('Kartu belum bisa diunduh, coba lagi'),
                ], 500);
            }

            return redirect()->back()->with('error', __('Kartu belum bisa diunduh, coba lagi'));
        }
    }

    private static function prepareTeacher(Teacher $teacher): Teacher
    {
        $teacher = clone $teacher;
        $teacher->nip_display = self::displayNip($teacher->nip);

        try {
            $teacher->qr_base64 = self::qrPngDataUri(self::qrTokenFor($teacher->nip, $teacher->qr_token));
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat QR kartu guru: '.$e->getMessage(), [
                'teacher_id' => $teacher->id,
            ]);
            $teacher->qr_base64 = null;
        }

        return $teacher;
    }

    private static function cacheKey(Collection $teachers): string
    {
        $setting = Setting::first();

        $teachersStamp = $teachers->map(fn (Teacher $teacher) => [
            'id' => $teacher->id,
            'nip' => $teacher->nip,
            'qr_token' => $teacher->qr_token,
            'name' => $teacher->name,
            'updated_at' => optional($teacher->updated_at)->timestamp ?? 0,
        ])->all();

        $key = md5(json_encode([
            'teachers' => $teachersStamp,
            'school_name' => Setting::getSchoolName(),
            'setting_updated_at' => optional($setting?->updated_at)->timestamp ?? 0,
            'design' => self::designStamp(),
        ]));

        return 'teacher_cards_v3_'.$key.'.pdf';
    }

    private static function singleCardCacheKey(Teacher $teacher): string
    {
        $setting = Setting::first();

        $key = md5(json_encode([
            'teacher' => [
                'id' => $teacher->id,
                'nip' => $teacher->nip,
                'qr_token' => $teacher->qr_token,
                'name' => $teacher->name,
                'updated_at' => optional($teacher->updated_at)->timestamp ?? 0,
            ],
            'school_name' => Setting::getSchoolName(),
            'setting_updated_at' => optional($setting?->updated_at)->timestamp ?? 0,
            'design' => self::designStamp(),
        ]));

        return 'teacher_card_single_v1_'.$key.'.pdf';
    }

    private static function designStamp(): string
    {
        $designFiles = [
            resource_path('views/admin/teachers/partials/card-single.blade.php'),
            resource_path('views/admin/teachers/partials/mass-card-pdf.blade.php'),
            resource_path('views/admin/teachers/print-card-pdf.blade.php'),
            public_path('images/card-bg-single-green.png'),
            public_path(Setting::getLogo()),
        ];

        return md5(implode('|', array_map(
            fn (string $path) => is_file($path) ? filemtime($path) : 0,
            $designFiles
        )));
    }

    private static function logoDataUri(): ?string
    {
        $logoPath = public_path(Setting::getLogo());

        if (! is_file($logoPath)) {
            return null;
        }

        $mime = mime_content_type($logoPath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoPath));
    }

    private static function initCacheDir(): void
    {
        if (self::$cacheDir === '') {
            self::$cacheDir = storage_path('app/downloads_cache/teacher_cards');
        }

        if (! File::isDirectory(self::$cacheDir)) {
            File::makeDirectory(self::$cacheDir, 0755, true, true);
        }
    }

    /**
     * Buat QR PNG data URI memakai GD, sama seperti kartu siswa massal.
     */
    private static function qrPngDataUri(string $token, int $targetPx = 378): string
    {
        $matrix = Encoder::encode(
            $token,
            ErrorCorrectionLevel::L()
        )->getMatrix();

        $matrixWidth = $matrix->getWidth();
        $matrixHeight = $matrix->getHeight();
        $moduleSize = max(1, (int) floor($targetPx / $matrixWidth));
        $width = $moduleSize * $matrixWidth;
        $height = $moduleSize * $matrixHeight;

        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $white);

        for ($y = 0; $y < $matrixHeight; $y++) {
            for ($x = 0; $x < $matrixWidth; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $x1 = $x * $moduleSize;
                    $y1 = $y * $moduleSize;
                    imagefilledrectangle($image, $x1, $y1, $x1 + $moduleSize - 1, $y1 + $moduleSize - 1, $black);
                }
            }
        }

        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }
}

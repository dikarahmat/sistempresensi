<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Cache in-memory static per PHP request lifecycle.
     */
    private static ?array $runtimeCache = null;

    protected static function booted(): void
    {
        static::saved(function () {
            self::clearCache();
            \App\Services\DownloadCacheService::clearAllCache();
        });

        static::deleted(function () {
            self::clearCache();
            \App\Services\DownloadCacheService::clearAllCache();
        });
    }

    /**
     * Bersihkan cache setting sistem (runtime memory & Cache facade).
     */
    public static function clearCache(): void
    {
        self::$runtimeCache = null;
        try {
            Cache::forget('system_settings_all');
            Cache::forget('setting_school_logo');
        } catch (\Throwable $e) {
            // Ignore cache exceptions on bootstrapping
        }
    }

    /**
     * Ambil seluruh konfigurasi sistem dalam bentuk associative array [key => value].
     * Menggunakan memory caching & persistent Cache::rememberForever.
     */
    public static function getAll(): array
    {
        if (self::$runtimeCache !== null) {
            return self::$runtimeCache;
        }

        try {
            self::$runtimeCache = Cache::rememberForever(
                'system_settings_all',
                function () {
                    return self::pluck('value', 'key')->toArray();
                }
            );
        } catch (\Throwable $e) {
            try {
                self::$runtimeCache = self::pluck('value', 'key')->toArray();
            } catch (\Throwable $e2) {
                self::$runtimeCache = [];
            }
        }

        return self::$runtimeCache ?? [];
    }

    /**
     * Ambil nilai pengaturan berdasarkan key dengan nilai default opsional.
     * Mengambil dari runtime cache O(1) in-memory tanpa query database berulang.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::getAll();
        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }
        return $default;
    }

    /**
     * Simpan atau perbarui nilai pengaturan, lalu perbarui cache.
     */
    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        self::clearCache();
    }

    /**
     * Ambil NPSN sekolah (Nomor Pokok Siswa Nasional).
     * Status: opsional. Kembalikan string 8 digit, atau kosong jika tidak diisi.
     */
    public static function getSchoolNpsn(): string
    {
        $value = static::get('school_npsn', '');
        if (filled($value)) {
            // Hanya ambil digit, lalu format 8 digit (jika ada)
            $digits = preg_replace('/[^0-9]/', '', (string) $value);
            if (strlen($digits) >= 8) {
                return substr($digits, 0, 8);
            }
            return $digits;
        }
        return '';
    }

    public static function getSchoolName(): string
    {
        return (string) self::get('school_name', 'SMP Presensi PGRI');
    }

    public static function getAppTitle(): string
    {
        return (string) self::get('app_title', 'Sistem Presensi Sekolah');
    }

    /**
     * Judul aplikasi untuk <title> tab browser.
     * Fallback berurutan: app_title -> nama sekolah -> nama aplikasi default.
     * Dipakai supaya <title> tidak pernah kosong dan tidak perlu hard-coded di view.
     */
    public static function getTitleAppName(): string
    {
        $appTitle = trim((string) (self::get('app_title') ?? ''));
        if ($appTitle !== '') {
            return $appTitle;
        }

        $schoolName = trim((string) (self::get('school_name') ?? ''));
        if ($schoolName !== '') {
            return $schoolName;
        }

        return 'Sistem Presensi Sekolah';
    }

    public static function getSchoolAddress(): string
    {
        return (string) self::get('school_address', 'Jl. Pendidikan No. 45, Kota Pelajar');
    }

    public static function getSchoolPhone(): string
    {
        $raw = self::get('school_phone');
        if ($raw) {
            // Return as stored (digits only after normalization)
            return (string) $raw;
        }
        return '(021) 555-1234';
    }

    public static function getSchoolPhoneNormalized(): string
    {
        $raw = self::get('school_phone', '');
        return preg_replace('/[^0-9]/', '', (string) $raw);
    }

    public static function getHeadmasterName(): string
    {
        return (string) self::get('headmaster_name', 'Drs. H. Ahmad Sudrajat, M.Pd');
    }

    public static function getHeadmasterNip(): string
    {
        return (string) self::get('headmaster_nip', '-');
    }

    public static function getCheckInTime(): string
    {
        return (string) self::get('check_in_time', '06:45');
    }

    public static function getLateLimitTime(): string
    {
        return (string) self::get('late_limit_time', '07:15');
    }

    /**
     * Jam pulang (Check-Out) - HANYA untuk logika Scanner/Gerbang.
     * TIDAK ditampilkan di form pengaturan, TIDAK disimpan via form.
     * Dipakai oleh ScannerController::processScan() untuk logika check-out.
     */
    public static function getCheckOutTime(): string
    {
        return (string) self::get('check_out_time', '14:00');
    }

    public static function getLogo(): string
    {
        return Cache::remember('setting_school_logo', 3600, function () {
            $logo = self::get('school_logo');
            if ($logo) {
                if (file_exists(public_path('storage/' . $logo))) {
                    return 'storage/' . $logo;
                }
                if (file_exists(public_path($logo))) {
                    return $logo;
                }
            }
            return 'images/logo.webp';
        });
    }

    /**
     * URL logo siap pakai di HTML (sidebar, login, favicon, dst).
     * Ditambah cache-busting ?v=<mtime file> supaya logo baru yang diunggah
     * langsung terlihat tanpa hard refresh, walau nama berkasnya sama.
     */
    public static function getLogoUrl(): string
    {
        $path = self::getLogo();
        $version = @filemtime(public_path($path));

        return asset($path) . '?v=' . ($version ?: time());
    }

    /**
     * Ambil logo sebagai data URI (base64) untuk PDF (DomPDF tidak bisa pakai URL).
     * Fallback ke logo bawaan jika file tidak ditemukan.
     */
    public static function getLogoDataUri(): string
    {
        $logo = self::get('school_logo');
        $fallback = public_path('images/logo.webp');
        $source = $logo ?: $fallback;

        // Coba dari storage/app/public (setelah symlink ingin)
        $localPath = 'storage/app/public/' . ltrim($logo ?? '', '/');
        if ($logo && file_exists($localPath)) {
            $source = $localPath;
        } elseif (file_exists($fallback)) {
            $source = $fallback;
        } else {
            // Fallback default: jika tidak ada file gambar, pakai placeholder teks
            return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="90" height="90" viewBox="0 0 100 100"><text y=".9em" font-size="90">🏫</text></svg>');
        }

        $mime = mime_content_type($source) ?: 'image/png';
        $data = file_get_contents($source);
        if ($data === false) {
            return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="90" height="90" viewBox="0 0 100 100"><text y=".9em" font-size="90">🏫</text></svg>');
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}

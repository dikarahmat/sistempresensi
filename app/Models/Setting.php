<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        });

        static::deleted(function () {
            self::clearCache();
        });
    }

    /**
     * Bersihkan cache setting sistem (runtime memory & Cache facade).
     */
    public static function clearCache(): void
    {
        self::$runtimeCache = null;
        try {
            \Illuminate\Support\Facades\Cache::forget('system_settings_all');
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
            self::$runtimeCache = \Illuminate\Support\Facades\Cache::rememberForever(
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

    public static function getSchoolName(): string
    {
        return (string) self::get('school_name', 'SMP Presensi PGRI');
    }

    public static function getAppTitle(): string
    {
        return (string) self::get('app_title', 'Sistem Presensi Sekolah');
    }

    public static function getSchoolAddress(): string
    {
        return (string) self::get('school_address', 'Jl. Pendidikan No. 45, Kota Pelajar');
    }

    public static function getSchoolPhone(): string
    {
        return (string) self::get('school_phone', '(021) 555-1234');
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

    public static function getCheckOutTime(): string
    {
        return (string) self::get('check_out_time', '14:30');
    }

    public static function getLogo(): string
    {
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
    }
}
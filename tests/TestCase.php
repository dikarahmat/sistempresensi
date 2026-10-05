<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Jaring pengaman kedua (setelah force="true" di phpunit.xml).
     *
     * Test di proyek ini memakai RefreshDatabase (= migrate:fresh). Bila
     * karena satu dan lain hal konfigurasi	test bypassing phpunit.xml
     * (config:cache, env OS, PHPUnit yang dijalankan tanpa konfigurasi,
     * atau .env ikut ter-load), RefreshDatabase akan ME-RESET database
     * MySQL lokal "presensi_smp" beserta seluruh data siswa/guru/kelas.
     *
     * Karena itu setiap test WAJIB berjalan di SQLite in-memory. Kalau tidak,
     * test dihentikan SEBELUM touching database, bukan sesudah.
     */
    protected function setUp(): void
    {
        // Jaring level 1 (pra-boot): gugurkan SEBELUM parent::setUp()
        // menjalankan RefreshDatabase (= migrate:fresh). Pengecekan config()
        // setelah parent::setUp() TERLAMBAT: migrate sudah jalan duluan.
        // Satu-satunya kondisi yang bisa membelokkan koneksi dari sqlite
        // adalah file cache config basi — deteksi langsung keberadaannya.
        $cachedConfig = __DIR__ . '/../bootstrap/cache/config.php';
        if (file_exists($cachedConfig)) {
            $this->fail(
                'TES DIBATALKAN: bootstrap/cache/config.php masih ada. '
                .'Hapus dulu dengan "php artisan config:clear" agar test '
                .'tidak me-reset database MySQL. File TIDAK dihapus otomatis '
                .'demi keamanan.'
            );
        }

        parent::setUp();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        $isSqliteMemory = $connection === 'sqlite' && in_array($database, [':memory:', ''], true);

        if (! $isSqliteMemory) {
            $this->fail(
                "TES DIBATASI: test hanya boleh memakai SQLite in-memory, "
                ."tapi konfigurasi memakai [{$connection} -> {$database}]. "
                .'Hentikan agar database development tidak ikut ter-reset. '
                .'Pastikan phpunit.xml tetap memakai <env ... force="true"/>.'
            );
        }
    }
}

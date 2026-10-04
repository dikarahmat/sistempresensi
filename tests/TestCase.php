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

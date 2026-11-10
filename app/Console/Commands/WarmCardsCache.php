<?php

namespace App\Console\Commands;

use App\Services\DownloadCacheService;
use Illuminate\Console\Command;

class WarmCardsCache extends Command
{
    protected $signature = 'cards:warm';

    protected $description = 'Pre-generate kartu presensi JPG semua siswa aktif ke cache supaya unduh massal cepat.';

    public function handle(): int
    {
        $this->info('Membuat cache kartu siswa aktif...');

        $bar = null;
        $total = \App\Models\Student::where('status', 'Aktif')->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $result = DownloadCacheService::warmStudentCards(function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. Berhasil: {$result['ok']}, gagal: {$result['failed']}, total: {$result['total']}.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
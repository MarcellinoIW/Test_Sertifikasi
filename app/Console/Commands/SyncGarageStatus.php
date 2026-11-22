<?php

namespace App\Console\Commands;

use App\Services\GarageService;
use Illuminate\Console\Command;

// command untuk menyesuaikan status garasi setiap hari
class SyncGarageStatus extends Command
{
    // nama command yang dipanggil di terminal
    protected $signature = 'garages:sync-status';

    // keterangan command
    protected $description = 'Sinkronkan status garasi (available/used) berdasarkan rental aktif hari ini';

    // function untuk menjalankan sinkron status garasi
    public function handle(GarageService $garageService): int
    {
        $result = $garageService->syncStatus();

        $this->info("Status garasi disinkronkan: {$result['available']} menjadi available, {$result['used']} menjadi used.");

        return self::SUCCESS;
    }
}

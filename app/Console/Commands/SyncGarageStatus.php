<?php

namespace App\Console\Commands;

use App\Services\GarageStatusService;
use Illuminate\Console\Command;

/**
 * Menyinkronkan status garasi (available/used) dengan rental aktif hari ini.
 * Dijadwalkan harian di routes/console.php.
 */
class SyncGarageStatus extends Command
{
    /**
     * Nama dan signature command.
     *
     * @var string
     */
    protected $signature = 'garages:sync-status';

    /**
     * Deskripsi command.
     *
     * @var string
     */
    protected $description = 'Sinkronkan status garasi (available/used) berdasarkan rental aktif hari ini';

    /**
     * Jalankan command.
     *
     * @param  GarageStatusService  $statusService  Service sinkronisasi status garasi.
     * @return int
     */
    public function handle(GarageStatusService $statusService): int
    {
        $result = $statusService->sync();

        $this->info("Status garasi disinkronkan: {$result['available']} menjadi available, {$result['used']} menjadi used.");

        return self::SUCCESS;
    }
}

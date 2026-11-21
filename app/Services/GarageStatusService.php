<?php

namespace App\Services;

use App\Enums\GarageStatus;
use App\Models\Garage;
use Carbon\CarbonInterface;

/**
 * Menyinkronkan kolom `status` garasi dengan data rental aktif.
 *
 * Garasi tanpa rental aktif pada tanggal acuan menjadi `available`,
 * sedangkan garasi yang memiliki rental aktif menjadi `used`.
 */
class GarageStatusService
{
    /**
     * Sinkronkan status seluruh garasi terhadap tanggal acuan.
     *
     * @param  CarbonInterface|null  $date  Tanggal acuan; default hari ini.
     * @return array{available: int, used: int} Jumlah baris yang diubah per status.
     */
    public function sync(?CarbonInterface $date = null): array
    {
        $date ??= today();

        $freed = Garage::query()
            ->where('status', GarageStatus::Used)
            ->availableOn($date)
            ->update(['status' => GarageStatus::Available]);

        $occupied = Garage::query()
            ->where('status', GarageStatus::Available)
            ->occupiedOn($date)
            ->update(['status' => GarageStatus::Used]);

        return ['available' => $freed, 'used' => $occupied];
    }
}

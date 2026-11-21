<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * Rincian biaya perpanjangan sewa: berapa blok bulan (25 x harga harian)
 * dan berapa hari harian, beserta total biaya dan tanggal selesai baru.
 */
final readonly class ExtensionBreakdown
{
    /**
     * @param  CarbonImmutable  $oldEndDate  Tanggal selesai lama.
     * @param  CarbonImmutable  $newEndDate  Tanggal selesai baru (lama + tambahan hari).
     * @param  int  $additionalDays  Total tambahan hari.
     * @param  int  $monthlyBlocks  Jumlah blok bulan yang dikenai tarif bulanan.
     * @param  int  $dailyDays  Sisa hari yang dikenai tarif harian.
     * @param  float  $dailyPrice  Harga harian (snapshot rental).
     * @param  float  $cost  Total biaya perpanjangan.
     */
    public function __construct(
        public CarbonImmutable $oldEndDate,
        public CarbonImmutable $newEndDate,
        public int $additionalDays,
        public int $monthlyBlocks,
        public int $dailyDays,
        public float $dailyPrice,
        public float $cost,
    ) {}

    /**
     * Representasi array untuk respons JSON / penyimpanan.
     *
     * @return array{old_end_date: string, new_end_date: string, additional_days: int, monthly_blocks: int, daily_days: int, daily_price: float, cost: float}
     */
    public function toArray(): array
    {
        return [
            'old_end_date' => $this->oldEndDate->toDateString(),
            'new_end_date' => $this->newEndDate->toDateString(),
            'additional_days' => $this->additionalDays,
            'monthly_blocks' => $this->monthlyBlocks,
            'daily_days' => $this->dailyDays,
            'daily_price' => $this->dailyPrice,
            'cost' => $this->cost,
        ];
    }
}

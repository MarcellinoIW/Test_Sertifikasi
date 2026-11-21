<?php

namespace App\DataTransferObjects;

use App\Enums\RentalType;
use Carbon\CarbonImmutable;

/**
 * Hasil perhitungan biaya sewa baru (sumber kebenaran untuk data rental).
 */
final readonly class RentalQuote
{
    /**
     * @param  RentalType  $rentalType  Jenis sewa.
     * @param  int  $days  Jumlah hari sewa.
     * @param  CarbonImmutable  $startDate  Tanggal mulai.
     * @param  CarbonImmutable  $endDate  Tanggal selesai (eksklusif).
     * @param  float  $dailyPrice  Harga harian garasi (snapshot).
     * @param  float  $rentCost  Biaya sewa.
     * @param  float|null  $batteryRemainingKwh  Sisa baterai (khusus mobil listrik).
     * @param  float|null  $chargedKwh  kWh yang diisi; null jika tidak ada pengisian.
     * @param  float|null  $electricityPricePerKwh  Harga per kWh (snapshot); null jika tidak ada pengisian.
     * @param  float  $electricityCost  Biaya isi listrik.
     * @param  float  $totalCost  Total = biaya sewa + biaya listrik.
     */
    public function __construct(
        public RentalType $rentalType,
        public int $days,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public float $dailyPrice,
        public float $rentCost,
        public ?float $batteryRemainingKwh,
        public ?float $chargedKwh,
        public ?float $electricityPricePerKwh,
        public float $electricityCost,
        public float $totalCost,
    ) {}

    /**
     * Atribut siap simpan ke tabel rentals.
     *
     * @return array<string, mixed>
     */
    public function toRentalAttributes(): array
    {
        return [
            'rental_type' => $this->rentalType,
            'days' => $this->days,
            'start_date' => $this->startDate->toDateString(),
            'end_date' => $this->endDate->toDateString(),
            'daily_price' => $this->dailyPrice,
            'rent_cost' => $this->rentCost,
            'battery_remaining_kwh' => $this->batteryRemainingKwh,
            'charged_kwh' => $this->chargedKwh,
            'electricity_price_per_kwh' => $this->electricityPricePerKwh,
            'electricity_cost' => $this->electricityCost,
            'total_cost' => $this->totalCost,
        ];
    }
}

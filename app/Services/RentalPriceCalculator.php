<?php

namespace App\Services;

use App\DataTransferObjects\ExtensionBreakdown;
use App\DataTransferObjects\RentalQuote;
use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Exceptions\BusinessRuleException;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Kalkulator harga sewa garasi. Seluruh method adalah fungsi murni
 * (tanpa akses DB) sehingga mudah di-unit-test.
 *
 * Aturan:
 * - Harian: maksimal (jumlah hari bulan berjalan - 1) hari, biaya = hari x harga harian.
 * - Bulanan: jumlah hari = jumlah hari bulan berjalan, biaya = 25 x harga harian.
 * - Listrik: hanya mobil listrik di garasi electric, biaya = (kapasitas - sisa) x harga/kWh.
 * - Perpanjangan: per blok bulan kalender dari tanggal selesai lama (lihat extensionCost()).
 */
class RentalPriceCalculator
{
    /**
     * @param  int  $monthlyMultiplier  Pengali harga harian untuk tarif bulanan.
     * @param  float  $batteryCapacityKwh  Kapasitas baterai mobil listrik (kWh).
     */
    public function __construct(
        private readonly int $monthlyMultiplier = 25,
        private readonly float $batteryCapacityKwh = 100,
    ) {}

    /**
     * Pengali tarif bulanan yang dipakai kalkulator.
     *
     * @return int
     */
    public function monthlyMultiplier(): int
    {
        return $this->monthlyMultiplier;
    }

    /**
     * Kapasitas baterai mobil listrik yang dipakai kalkulator.
     *
     * @return float
     */
    public function batteryCapacityKwh(): float
    {
        return $this->batteryCapacityKwh;
    }

    /**
     * Batas maksimal hari untuk sewa harian = jumlah hari di bulan tanggal tersebut - 1.
     * Contoh: November -> 29, Januari -> 30, Februari 2028 (kabisat) -> 28.
     *
     * @param  CarbonInterface  $date  Tanggal acuan (biasanya hari ini).
     * @return int
     */
    public function maxDailyDays(CarbonInterface $date): int
    {
        return $date->daysInMonth - 1;
    }

    /**
     * Jumlah hari sewa bulanan = jumlah hari di bulan tanggal tersebut.
     * Contoh: November -> 30, Februari 2028 -> 29.
     *
     * @param  CarbonInterface  $date  Tanggal acuan (biasanya hari ini).
     * @return int
     */
    public function monthlyDays(CarbonInterface $date): int
    {
        return $date->daysInMonth;
    }

    /**
     * Harga satu bulan = pengali bulanan x harga harian.
     *
     * @param  float  $dailyPrice  Harga harian.
     * @return float
     */
    public function monthlyPrice(float $dailyPrice): float
    {
        return round($this->monthlyMultiplier * $dailyPrice, 2);
    }

    /**
     * Biaya sewa sesuai jenis sewa.
     *
     * @param  RentalType  $type  Jenis sewa.
     * @param  int  $days  Jumlah hari (diabaikan untuk bulanan).
     * @param  float  $dailyPrice  Harga harian.
     * @return float
     */
    public function rentCost(RentalType $type, int $days, float $dailyPrice): float
    {
        return match ($type) {
            RentalType::Daily => round($days * $dailyPrice, 2),
            RentalType::Monthly => $this->monthlyPrice($dailyPrice),
        };
    }

    /**
     * Apakah pengisian listrik berlaku: hanya mobil listrik di garasi electric.
     *
     * @param  VehicleType  $vehicleType  Tipe kendaraan.
     * @param  GarageType  $garageType  Tipe garasi.
     * @return bool
     */
    public function chargesElectricity(VehicleType $vehicleType, GarageType $garageType): bool
    {
        return $vehicleType->isElectric() && $garageType->hasCharging();
    }

    /**
     * Jumlah kWh yang diisi = kapasitas baterai - sisa baterai (minimal 0).
     *
     * @param  float  $batteryRemainingKwh  Sisa baterai (kWh).
     * @return float
     */
    public function chargedKwh(float $batteryRemainingKwh): float
    {
        return round(max(0, $this->batteryCapacityKwh - $batteryRemainingKwh), 2);
    }

    /**
     * Biaya isi listrik. Nol jika kendaraan bukan mobil listrik atau garasi bukan tipe electric.
     * Contoh: sisa 30 kWh, Rp 2.500/kWh -> (100 - 30) x 2.500 = Rp 175.000.
     *
     * @param  VehicleType  $vehicleType  Tipe kendaraan.
     * @param  GarageType  $garageType  Tipe garasi.
     * @param  float|null  $batteryRemainingKwh  Sisa baterai (kWh).
     * @param  float|null  $pricePerKwh  Harga isi listrik per kWh.
     * @return float
     */
    public function electricityCost(VehicleType $vehicleType, GarageType $garageType, ?float $batteryRemainingKwh, ?float $pricePerKwh): float
    {
        if (! $this->chargesElectricity($vehicleType, $garageType) || $batteryRemainingKwh === null || $pricePerKwh === null) {
            return 0.0;
        }

        return round($this->chargedKwh($batteryRemainingKwh) * $pricePerKwh, 2);
    }

    /**
     * Menghitung seluruh biaya sewa baru.
     *
     * @param  RentalType  $type  Jenis sewa.
     * @param  int|null  $days  Jumlah hari (wajib untuk harian; diabaikan untuk bulanan).
     * @param  CarbonInterface  $startDate  Tanggal mulai (hari ini).
     * @param  float  $dailyPrice  Harga harian garasi.
     * @param  VehicleType  $vehicleType  Tipe kendaraan.
     * @param  GarageType  $garageType  Tipe garasi.
     * @param  float|null  $batteryRemainingKwh  Sisa baterai (khusus mobil listrik).
     * @param  float|null  $pricePerKwh  Harga isi listrik per kWh garasi.
     * @return RentalQuote
     *
     * @throws BusinessRuleException Jika jumlah hari sewa harian di luar batas.
     */
    public function quote(
        RentalType $type,
        ?int $days,
        CarbonInterface $startDate,
        float $dailyPrice,
        VehicleType $vehicleType,
        GarageType $garageType,
        ?float $batteryRemainingKwh = null,
        ?float $pricePerKwh = null,
    ): RentalQuote {
        $start = CarbonImmutable::instance($startDate)->startOfDay();

        if ($type === RentalType::Daily) {
            $max = $this->maxDailyDays($start);
            if ($days === null || $days < 1 || $days > $max) {
                throw new BusinessRuleException("Jumlah hari sewa harian harus antara 1 sampai {$max} hari.", 'days');
            }
        } else {
            $days = $this->monthlyDays($start);
        }

        $rentCost = $this->rentCost($type, $days, $dailyPrice);
        $charges = $this->chargesElectricity($vehicleType, $garageType);
        $electricityCost = $this->electricityCost($vehicleType, $garageType, $batteryRemainingKwh, $pricePerKwh);

        return new RentalQuote(
            rentalType: $type,
            days: $days,
            startDate: $start,
            endDate: $start->addDays($days),
            dailyPrice: $dailyPrice,
            rentCost: $rentCost,
            batteryRemainingKwh: $vehicleType->isElectric() ? $batteryRemainingKwh : null,
            chargedKwh: $charges && $batteryRemainingKwh !== null ? $this->chargedKwh($batteryRemainingKwh) : null,
            electricityPricePerKwh: $charges ? $pricePerKwh : null,
            electricityCost: $electricityCost,
            totalCost: round($rentCost + $electricityCost, 2),
        );
    }

    /**
     * Biaya perpanjangan sewa dengan algoritma blok bulan kalender:
     *
     *     cursor = tanggal selesai lama; sisa = tambahan hari
     *     selama sisa > 0:
     *         dim = jumlah hari di bulan milik cursor
     *         jika sisa >= dim: biaya += 25 x harian; cursor += dim hari; sisa -= dim
     *         selain itu:       biaya += sisa x harian; sisa = 0
     *
     * Contoh: selesai 25 Des + 45 hari -> 1 blok bulan + 14 hari harian.
     *
     * @param  CarbonInterface  $oldEnd  Tanggal selesai lama.
     * @param  int  $days  Tambahan hari (minimal 1).
     * @param  float  $dailyPrice  Harga harian (snapshot rental).
     * @return ExtensionBreakdown
     *
     * @throws BusinessRuleException Jika tambahan hari kurang dari 1.
     */
    public function extensionCost(CarbonInterface $oldEnd, int $days, float $dailyPrice): ExtensionBreakdown
    {
        if ($days < 1) {
            throw new BusinessRuleException('Tambahan hari minimal 1 hari.', 'additional_days');
        }

        $start = CarbonImmutable::instance($oldEnd)->startOfDay();
        $cursor = $start;
        $remaining = $days;
        $monthlyBlocks = 0;
        $dailyDays = 0;
        $cost = 0.0;

        while ($remaining > 0) {
            $daysInMonth = $cursor->daysInMonth;

            if ($remaining >= $daysInMonth) {
                $cost += $this->monthlyPrice($dailyPrice);
                $cursor = $cursor->addDays($daysInMonth);
                $remaining -= $daysInMonth;
                $monthlyBlocks++;
            } else {
                $cost += $remaining * $dailyPrice;
                $dailyDays = $remaining;
                $remaining = 0;
            }
        }

        return new ExtensionBreakdown(
            oldEndDate: $start,
            newEndDate: $start->addDays($days),
            additionalDays: $days,
            monthlyBlocks: $monthlyBlocks,
            dailyDays: $dailyDays,
            dailyPrice: $dailyPrice,
            cost: round($cost, 2),
        );
    }
}

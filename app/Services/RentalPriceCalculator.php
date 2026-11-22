<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

// service untuk menghitung harga sewa, biaya listrik, dan biaya perpanjangan
class RentalPriceCalculator
{
    // pengali harga bulanan
    private readonly int $monthlyMultiplier;

    // kapasitas baterai mobil listrik (kWh)
    private readonly float $batteryCapacityKwh;

    // function constructor untuk mengambil pengali bulanan dan kapasitas baterai
    public function __construct(?int $monthlyMultiplier = null, ?float $batteryCapacityKwh = null)
    {
        $this->monthlyMultiplier = $monthlyMultiplier ?? (int) config('garasi.monthly_multiplier');
        $this->batteryCapacityKwh = $batteryCapacityKwh ?? (float) config('garasi.battery_capacity_kwh');
    }

    // function untuk mengambil pengali harga bulanan
    public function monthlyMultiplier(): int
    {
        return $this->monthlyMultiplier;
    }

    // function untuk mengambil kapasitas baterai mobil listrik
    public function batteryCapacityKwh(): float
    {
        return $this->batteryCapacityKwh;
    }

    // function untuk menghitung batas maksimal hari sewa harian
    public function maxDailyDays(CarbonInterface $date): int
    {
        return $date->daysInMonth - 1;
    }

    // function untuk menghitung jumlah hari sewa bulanan
    public function monthlyDays(CarbonInterface $date): int
    {
        return $date->daysInMonth;
    }

    // function untuk menghitung harga sewa bulanan
    public function monthlyPrice(float $dailyPrice): float
    {
        return round($this->monthlyMultiplier * $dailyPrice, 2);
    }

    // function untuk menghitung biaya sewa
    public function rentCost(RentalType $type, int $days, float $dailyPrice): float
    {
        return match ($type) {
            RentalType::Daily => round($days * $dailyPrice, 2),
            RentalType::Monthly => $this->monthlyPrice($dailyPrice),
        };
    }

    // function untuk mengecek apakah kena biaya isi listrik
    public function chargesElectricity(VehicleType $vehicleType, GarageType $garageType): bool
    {
        return $vehicleType->isElectric() && $garageType->hasCharging();
    }

    // function untuk menghitung jumlah kWh yang diisi
    public function chargedKwh(float $batteryRemainingKwh): float
    {
        return round(max(0, $this->batteryCapacityKwh - $batteryRemainingKwh), 2);
    }

    // function untuk menghitung biaya isi listrik
    public function electricityCost(VehicleType $vehicleType, GarageType $garageType, ?float $batteryRemainingKwh, ?float $pricePerKwh): float
    {
        if (! $this->chargesElectricity($vehicleType, $garageType) || $batteryRemainingKwh === null || $pricePerKwh === null) {
            return 0.0;
        }

        return round($this->chargedKwh($batteryRemainingKwh) * $pricePerKwh, 2);
    }

    // function untuk menghitung total harga sewa
    public function quote(
        RentalType $type,
        ?int $days,
        CarbonInterface $startDate,
        float $dailyPrice,
        VehicleType $vehicleType,
        GarageType $garageType,
        ?float $batteryRemainingKwh = null,
        ?float $pricePerKwh = null,
    ): array {
        $start = CarbonImmutable::instance($startDate)->startOfDay();

        if ($type === RentalType::Daily) {
            $max = $this->maxDailyDays($start);
            if ($days === null || $days < 1 || $days > $max) {
                throw ValidationException::withMessages(['days' => "Jumlah hari sewa harian harus antara 1 sampai {$max} hari."]);
            }
        } else {
            $days = $this->monthlyDays($start);
        }

        $rentCost = $this->rentCost($type, $days, $dailyPrice);
        $charges = $this->chargesElectricity($vehicleType, $garageType);
        $electricityCost = $this->electricityCost($vehicleType, $garageType, $batteryRemainingKwh, $pricePerKwh);

        return [
            'rental_type' => $type,
            'days' => $days,
            'start_date' => $start,
            'end_date' => $start->addDays($days),
            'daily_price' => $dailyPrice,
            'rent_cost' => $rentCost,
            'battery_remaining_kwh' => $vehicleType->isElectric() ? $batteryRemainingKwh : null,
            'charged_kwh' => $charges && $batteryRemainingKwh !== null ? $this->chargedKwh($batteryRemainingKwh) : null,
            'electricity_price_per_kwh' => $charges ? $pricePerKwh : null,
            'electricity_cost' => $electricityCost,
            'total_cost' => round($rentCost + $electricityCost, 2),
        ];
    }

    // function untuk menghitung biaya perpanjangan sewa
    public function extensionCost(CarbonInterface $oldEnd, int $days, float $dailyPrice): array
    {
        if ($days < 1) {
            throw ValidationException::withMessages(['additional_days' => 'Tambahan hari minimal 1 hari.']);
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

        $cost = round($cost, 2);
        $dailySubtotal = round($dailyDays * $dailyPrice, 2);

        return [
            'old_end_date' => $start,
            'new_end_date' => $start->addDays($days),
            'additional_days' => $days,
            'monthly_blocks' => $monthlyBlocks,
            'daily_days' => $dailyDays,
            'daily_price' => $dailyPrice,
            'monthly_subtotal' => round($cost - $dailySubtotal, 2),
            'daily_subtotal' => $dailySubtotal,
            'cost' => $cost,
        ];
    }
}

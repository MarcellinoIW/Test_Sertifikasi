<?php

namespace App\Enums;

// enum untuk tipe kendaraan dan aturan garasi yang boleh dipakai
enum VehicleType: string
{
    case Bicycle = 'bicycle';
    case Motorcycle = 'motorcycle';
    case Car = 'car';
    case ElectricCar = 'electric_car';

    // function untuk menampilkan label dalam bahasa Indonesia
    public function label(): string
    {
        return match ($this) {
            self::Bicycle => 'Sepeda',
            self::Motorcycle => 'Sepeda Motor',
            self::Car => 'Mobil',
            self::ElectricCar => 'Mobil Listrik',
        };
    }

    // function untuk mengambil tipe garasi yang boleh dipakai kendaraan ini
    public function allowedGarageTypes(): array
    {
        return match ($this) {
            self::Bicycle, self::Motorcycle => [GarageType::Bicycle],
            self::Car => [GarageType::Car],
            self::ElectricCar => [GarageType::Car, GarageType::Electric],
        };
    }

    // function untuk mengecek kendaraan boleh masuk garasi tertentu atau tidak
    public function canUseGarage(GarageType $garageType): bool
    {
        return in_array($garageType, $this->allowedGarageTypes(), true);
    }

    // function untuk mengecek kendaraan wajib punya nomor plat atau tidak
    public function requiresPlate(): bool
    {
        return $this !== self::Bicycle;
    }

    // function untuk mengecek kendaraan termasuk mobil listrik atau tidak
    public function isElectric(): bool
    {
        return $this === self::ElectricCar;
    }

    // function untuk mengambil semua nilai enum
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

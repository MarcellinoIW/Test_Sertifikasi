<?php

namespace App\Enums;

/**
 * Tipe kendaraan penyewa, sekaligus satu-satunya sumber aturan
 * kompatibilitas kendaraan dengan tipe garasi.
 */
enum VehicleType: string
{
    case Bicycle = 'bicycle';
    case Motorcycle = 'motorcycle';
    case Car = 'car';
    case ElectricCar = 'electric_car';

    /**
     * Label berbahasa Indonesia untuk ditampilkan di UI.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Bicycle => 'Sepeda',
            self::Motorcycle => 'Sepeda Motor',
            self::Car => 'Mobil',
            self::ElectricCar => 'Mobil Listrik',
        };
    }

    /**
     * Tipe garasi yang boleh ditempati kendaraan ini.
     *
     * - Garasi Sepeda (S): sepeda, sepeda motor
     * - Garasi Mobil (C): mobil, mobil listrik
     * - Garasi Mobil Listrik (E): mobil listrik saja
     *
     * @return array<int, GarageType>
     */
    public function allowedGarageTypes(): array
    {
        return match ($this) {
            self::Bicycle, self::Motorcycle => [GarageType::Bicycle],
            self::Car => [GarageType::Car],
            self::ElectricCar => [GarageType::Car, GarageType::Electric],
        };
    }

    /**
     * Apakah kendaraan ini boleh menempati garasi bertipe tertentu.
     *
     * @param  GarageType  $garageType  Tipe garasi yang akan dicek.
     * @return bool
     */
    public function canUseGarage(GarageType $garageType): bool
    {
        return in_array($garageType, $this->allowedGarageTypes(), true);
    }

    /**
     * Apakah nomor plat wajib diisi (semua kendaraan kecuali sepeda).
     *
     * @return bool
     */
    public function requiresPlate(): bool
    {
        return $this !== self::Bicycle;
    }

    /**
     * Apakah kendaraan ini mobil listrik (memiliki baterai).
     *
     * @return bool
     */
    public function isElectric(): bool
    {
        return $this === self::ElectricCar;
    }

    /**
     * Daftar nilai enum, berguna untuk aturan validasi `in:`.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

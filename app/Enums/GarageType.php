<?php

namespace App\Enums;

/**
 * Tipe garasi beserta prefix kode garasinya.
 */
enum GarageType: string
{
    case Bicycle = 'bicycle';
    case Car = 'car';
    case Electric = 'electric';

    /**
     * Prefix huruf untuk kode garasi (misal S001, C010, E005).
     *
     * @return string
     */
    public function prefix(): string
    {
        return match ($this) {
            self::Bicycle => 'S',
            self::Car => 'C',
            self::Electric => 'E',
        };
    }

    /**
     * Label berbahasa Indonesia untuk ditampilkan di UI.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Bicycle => 'Sepeda',
            self::Car => 'Mobil',
            self::Electric => 'Mobil Listrik',
        };
    }

    /**
     * Apakah garasi tipe ini menyediakan pengisian listrik (dan karenanya
     * wajib memiliki harga per kWh).
     *
     * @return bool
     */
    public function hasCharging(): bool
    {
        return $this === self::Electric;
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

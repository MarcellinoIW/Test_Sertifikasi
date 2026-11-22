<?php

namespace App\Enums;

// enum untuk tipe garasi (S / C / E)
enum GarageType: string
{
    case Bicycle = 'bicycle';
    case Car = 'car';
    case Electric = 'electric';

    // function untuk mengambil huruf depan kode garasi (S / C / E)
    public function prefix(): string
    {
        return match ($this) {
            self::Bicycle => 'S',
            self::Car => 'C',
            self::Electric => 'E',
        };
    }

    // function untuk menampilkan label dalam bahasa Indonesia
    public function label(): string
    {
        return match ($this) {
            self::Bicycle => 'Sepeda',
            self::Car => 'Mobil',
            self::Electric => 'Mobil Listrik',
        };
    }

    // function untuk mengecek garasi punya fasilitas isi listrik atau tidak
    public function hasCharging(): bool
    {
        return $this === self::Electric;
    }

    // function untuk mengambil semua nilai enum
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

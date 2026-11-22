<?php

namespace App\Enums;

// enum untuk jenis sewa (harian / bulanan)
enum RentalType: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';

    // function untuk menampilkan label dalam bahasa Indonesia
    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Harian',
            self::Monthly => 'Bulanan',
        };
    }

    // function untuk mengambil semua nilai enum
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

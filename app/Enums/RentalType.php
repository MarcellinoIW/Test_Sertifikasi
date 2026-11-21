<?php

namespace App\Enums;

/**
 * Jenis sewa garasi.
 */
enum RentalType: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';

    /**
     * Label berbahasa Indonesia untuk ditampilkan di UI.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Harian',
            self::Monthly => 'Bulanan',
        };
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

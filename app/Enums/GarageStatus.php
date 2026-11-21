<?php

namespace App\Enums;

/**
 * Status ketersediaan garasi.
 */
enum GarageStatus: string
{
    case Available = 'available';
    case Used = 'used';

    /**
     * Label berbahasa Indonesia untuk ditampilkan di UI.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Used => 'Terpakai',
        };
    }

    /**
     * Warna badge Bootstrap yang sesuai dengan status.
     *
     * @return string
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Used => 'secondary',
        };
    }
}

<?php

namespace App\Enums;

// enum untuk status garasi (tersedia / terpakai)
enum GarageStatus: string
{
    case Available = 'available';
    case Used = 'used';

    // function untuk menampilkan label dalam bahasa Indonesia
    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Used => 'Terpakai',
        };
    }

    // function untuk menentukan warna badge di tampilan
    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Used => 'secondary',
        };
    }
}

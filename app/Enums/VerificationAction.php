<?php

namespace App\Enums;

// enum untuk jenis riwayat verifikasi penyewa
enum VerificationAction: string
{
    case Registered = 'registered';
    case ReRegistered = 're_registered';
    case Approved = 'approved';
    case Rejected = 'rejected';

    // function untuk menampilkan label dalam bahasa Indonesia
    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Mendaftar',
            self::ReRegistered => 'Mendaftar ulang',
            self::Approved => 'Diterima',
            self::Rejected => 'Ditolak',
        };
    }

    // function untuk menentukan warna badge di tampilan
    public function badgeClass(): string
    {
        return match ($this) {
            self::Registered, self::ReRegistered => 'secondary',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    // function untuk mengambil semua nilai enum
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

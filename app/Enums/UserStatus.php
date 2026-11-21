<?php

namespace App\Enums;

/**
 * Status verifikasi akun penyewa.
 */
enum UserStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * Label berbahasa Indonesia untuk ditampilkan di UI.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Approved => 'Diterima',
            self::Rejected => 'Ditolak',
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
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}

<?php

namespace App\Enums;

/**
 * Peran (role) pengguna aplikasi.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Tenant = 'tenant';

    /**
     * Label berbahasa Indonesia untuk ditampilkan di UI.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Tenant => 'Penyewa',
        };
    }
}

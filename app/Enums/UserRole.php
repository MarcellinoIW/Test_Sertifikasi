<?php

namespace App\Enums;

// enum untuk role user (admin / penyewa)
enum UserRole: string
{
    case Admin = 'admin';
    case Tenant = 'tenant';

    // function untuk menampilkan label dalam bahasa Indonesia
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Tenant => 'Penyewa',
        };
    }
}

<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\User;

/**
 * Otorisasi akses data rental.
 */
class RentalPolicy
{
    /**
     * Admin boleh melihat semua rental; penyewa hanya miliknya sendiri.
     *
     * @param  User  $user  Pengguna yang login.
     * @param  Rental  $rental  Rental yang diakses.
     * @return bool
     */
    public function view(User $user, Rental $rental): bool
    {
        return $user->isAdmin() || $rental->user_id === $user->id;
    }
}

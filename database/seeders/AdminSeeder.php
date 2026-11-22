<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

// seeder untuk membuat akun admin
class AdminSeeder extends Seeder
{
    // function untuk membuat akun admin
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@garasi.test'],
            [
                'nik' => null,
                'name' => 'Administrator',
                'phone' => '081200000000',
                'password' => 'password',
                'role' => UserRole::Admin,
                'status' => UserStatus::Approved,
            ],
        );
    }
}

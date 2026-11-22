<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// factory untuk membuat data user palsu
class UserFactory extends Factory
{
    // password yang dipakai ulang supaya factory cepat
    protected static ?string $password;

    // function untuk data default factory
    public function definition(): array
    {
        return [
            'nik' => fake()->unique()->numerify('################'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Tenant,
            'status' => UserStatus::Approved,
            'remember_token' => Str::random(10),
        ];
    }

    // function untuk membuat data akun admin
    public function admin(): static
    {
        return $this->state(fn () => [
            'nik' => null,
            'role' => UserRole::Admin,
            'status' => UserStatus::Approved,
        ]);
    }

    // function untuk membuat data penyewa yang sudah disetujui
    public function approved(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Approved]);
    }

    // function untuk membuat data penyewa yang menunggu verifikasi
    public function pending(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Pending]);
    }

    // function untuk membuat data penyewa yang ditolak
    public function rejected(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Rejected]);
    }
}

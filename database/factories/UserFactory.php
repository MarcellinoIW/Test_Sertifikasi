<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory untuk model User. Default: penyewa berstatus approved.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Password ter-hash yang dipakai ulang agar factory cepat.
     */
    protected static ?string $password;

    /**
     * Definisi atribut default.
     *
     * @return array<string, mixed>
     */
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

    /**
     * State: akun admin (tanpa NIK, selalu approved).
     *
     * @return static
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'nik' => null,
            'role' => UserRole::Admin,
            'status' => UserStatus::Approved,
        ]);
    }

    /**
     * State: penyewa yang sudah disetujui.
     *
     * @return static
     */
    public function approved(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Approved]);
    }

    /**
     * State: penyewa yang menunggu verifikasi.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Pending]);
    }

    /**
     * State: penyewa yang pendaftarannya ditolak.
     *
     * @return static
     */
    public function rejected(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Rejected]);
    }
}

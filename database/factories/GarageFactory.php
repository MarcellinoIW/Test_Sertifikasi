<?php

namespace Database\Factories;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

// factory untuk membuat data garasi palsu
class GarageFactory extends Factory
{
    // penghitung nomor kode garasi terakhir
    protected static array $sequence = [];

    // function untuk data default factory
    public function definition(): array
    {
        return [
            'type' => GarageType::Car,
            'code' => fn (array $attributes) => static::nextCode($attributes['type']),
            'daily_price' => 20000,
            'electricity_price_per_kwh' => null,
            'status' => GarageStatus::Available,
        ];
    }

    // function untuk membuat data garasi sepeda
    public function bicycle(): static
    {
        return $this->state(fn () => [
            'type' => GarageType::Bicycle,
            'daily_price' => 5000,
            'electricity_price_per_kwh' => null,
        ]);
    }

    // function untuk membuat data garasi mobil
    public function car(): static
    {
        return $this->state(fn () => [
            'type' => GarageType::Car,
            'daily_price' => 20000,
            'electricity_price_per_kwh' => null,
        ]);
    }

    // function untuk membuat data garasi mobil listrik
    public function electric(): static
    {
        return $this->state(fn () => [
            'type' => GarageType::Electric,
            'daily_price' => 30000,
            'electricity_price_per_kwh' => 2500,
        ]);
    }

    // function untuk membuat data garasi yang sedang dipakai
    public function used(): static
    {
        return $this->state(fn () => ['status' => GarageStatus::Used]);
    }

    // function untuk membuat kode garasi berikutnya
    protected static function nextCode(GarageType|string $type): string
    {
        $type = $type instanceof GarageType ? $type : GarageType::from($type);
        $prefix = $type->prefix();

        $lastCode = Garage::query()->where('code', 'like', $prefix.'%')->max('code');
        $lastInDb = $lastCode ? (int) substr((string) $lastCode, 1) : 0;

        $next = max($lastInDb, static::$sequence[$prefix] ?? 0) + 1;
        static::$sequence[$prefix] = $next;

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}

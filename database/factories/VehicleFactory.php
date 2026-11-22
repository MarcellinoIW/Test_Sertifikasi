<?php

namespace Database\Factories;

use App\Enums\VehicleType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

// factory untuk membuat data kendaraan palsu
class VehicleFactory extends Factory
{
    // function untuk data default factory
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => VehicleType::Car,
            'plate_number' => static::fakePlate(),
            'brand_model' => fake()->randomElement(['Toyota Avanza', 'Honda Brio', 'Daihatsu Xenia', 'Suzuki Ertiga']),
            'battery_capacity_kwh' => null,
        ];
    }

    // function untuk membuat data sepeda
    public function bicycle(): static
    {
        return $this->state(fn () => [
            'type' => VehicleType::Bicycle,
            'plate_number' => null,
            'brand_model' => fake()->randomElement(['Polygon Xtrada', 'United Detroit', 'Brompton C Line']),
            'battery_capacity_kwh' => null,
        ]);
    }

    // function untuk membuat data sepeda motor
    public function motorcycle(): static
    {
        return $this->state(fn () => [
            'type' => VehicleType::Motorcycle,
            'plate_number' => static::fakePlate(),
            'brand_model' => fake()->randomElement(['Honda Beat', 'Yamaha NMAX', 'Honda Vario']),
            'battery_capacity_kwh' => null,
        ]);
    }

    // function untuk membuat data mobil
    public function car(): static
    {
        return $this->state(fn () => [
            'type' => VehicleType::Car,
            'battery_capacity_kwh' => null,
        ]);
    }

    // function untuk membuat data mobil listrik
    public function electricCar(): static
    {
        return $this->state(fn () => [
            'type' => VehicleType::ElectricCar,
            'plate_number' => static::fakePlate(),
            'brand_model' => fake()->randomElement(['Hyundai Ioniq 5', 'Wuling Air ev', 'BYD Atto 3']),
            'battery_capacity_kwh' => config('garasi.battery_capacity_kwh'),
        ]);
    }

    // function untuk membuat nomor plat acak
    protected static function fakePlate(): string
    {
        return fake()->unique()->regexify('[A-Z]{1,2} [1-9][0-9]{3} [A-Z]{2,3}');
    }
}

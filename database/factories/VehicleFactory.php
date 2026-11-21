<?php

namespace Database\Factories;

use App\Enums\VehicleType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model Vehicle. Default: mobil bensin berplat nomor.
 *
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Definisi atribut default.
     *
     * @return array<string, mixed>
     */
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

    /**
     * State: sepeda (tanpa plat nomor).
     *
     * @return static
     */
    public function bicycle(): static
    {
        return $this->state(fn () => [
            'type' => VehicleType::Bicycle,
            'plate_number' => null,
            'brand_model' => fake()->randomElement(['Polygon Xtrada', 'United Detroit', 'Brompton C Line']),
            'battery_capacity_kwh' => null,
        ]);
    }

    /**
     * State: sepeda motor.
     *
     * @return static
     */
    public function motorcycle(): static
    {
        return $this->state(fn () => [
            'type' => VehicleType::Motorcycle,
            'plate_number' => static::fakePlate(),
            'brand_model' => fake()->randomElement(['Honda Beat', 'Yamaha NMAX', 'Honda Vario']),
            'battery_capacity_kwh' => null,
        ]);
    }

    /**
     * State: mobil bensin.
     *
     * @return static
     */
    public function car(): static
    {
        return $this->state(fn () => [
            'type' => VehicleType::Car,
            'battery_capacity_kwh' => null,
        ]);
    }

    /**
     * State: mobil listrik dengan kapasitas baterai dari config.
     *
     * @return static
     */
    public function electricCar(): static
    {
        return $this->state(fn () => [
            'type' => VehicleType::ElectricCar,
            'plate_number' => static::fakePlate(),
            'brand_model' => fake()->randomElement(['Hyundai Ioniq 5', 'Wuling Air ev', 'BYD Atto 3']),
            'battery_capacity_kwh' => config('garasi.battery_capacity_kwh'),
        ]);
    }

    /**
     * Membuat nomor plat acak bergaya Indonesia, misal "B 1234 XYZ".
     *
     * @return string
     */
    protected static function fakePlate(): string
    {
        return fake()->unique()->regexify('[A-Z]{1,2} [1-9][0-9]{3} [A-Z]{2,3}');
    }
}

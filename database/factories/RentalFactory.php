<?php

namespace Database\Factories;

use App\Enums\RentalType;
use App\Models\Garage;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RentalPriceCalculator;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

// factory untuk membuat data sewa palsu
class RentalFactory extends Factory
{
    // function untuk data default factory
    public function definition(): array
    {
        $days = 3;
        $dailyPrice = 20000;

        return [
            'user_id' => User::factory(),
            'garage_id' => Garage::factory()->car(),
            'vehicle_id' => fn (array $attributes) => Vehicle::factory()->car()->state([
                'user_id' => $attributes['user_id'],
            ]),
            'rental_type' => RentalType::Daily,
            'days' => $days,
            'start_date' => today(),
            'end_date' => today()->addDays($days),
            'daily_price' => $dailyPrice,
            'rent_cost' => $days * $dailyPrice,
            'battery_remaining_kwh' => null,
            'charged_kwh' => null,
            'electricity_price_per_kwh' => null,
            'electricity_cost' => 0,
            'total_cost' => $days * $dailyPrice,
            'payment_proof' => 'payment-proofs/demo.png',
        ];
    }

    // function untuk membuat data sewa harian
    public function daily(CarbonInterface $start, int $days): static
    {
        return $this->state(function (array $attributes) use ($start, $days) {
            $rentCost = app(RentalPriceCalculator::class)->rentCost(RentalType::Daily, $days, (float) $attributes['daily_price']);

            return [
                'rental_type' => RentalType::Daily,
                'days' => $days,
                'start_date' => $start->copy()->startOfDay(),
                'end_date' => $start->copy()->startOfDay()->addDays($days),
                'rent_cost' => $rentCost,
                'total_cost' => $rentCost + (float) ($attributes['electricity_cost'] ?? 0),
            ];
        });
    }

    // function untuk membuat data sewa bulanan
    public function monthly(CarbonInterface $start): static
    {
        return $this->state(function (array $attributes) use ($start) {
            $calculator = app(RentalPriceCalculator::class);
            $days = $calculator->monthlyDays($start);
            $rentCost = $calculator->rentCost(RentalType::Monthly, $days, (float) $attributes['daily_price']);

            return [
                'rental_type' => RentalType::Monthly,
                'days' => $days,
                'start_date' => $start->copy()->startOfDay(),
                'end_date' => $start->copy()->startOfDay()->addDays($days),
                'rent_cost' => $rentCost,
                'total_cost' => $rentCost + (float) ($attributes['electricity_cost'] ?? 0),
            ];
        });
    }
}

<?php

namespace Database\Factories;

use App\Enums\RentalType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RentalPriceCalculator;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model Rental. Default: sewa harian 3 hari mulai hari ini
 * di garasi mobil, dengan biaya konsisten terhadap harga harian.
 *
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    /**
     * Definisi atribut default.
     *
     * @return array<string, mixed>
     */
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

    /**
     * State: sewa harian pada tanggal mulai dan jumlah hari tertentu.
     * Biaya dihitung ulang dari harga harian snapshot.
     *
     * @param  CarbonInterface  $start  Tanggal mulai.
     * @param  int  $days  Jumlah hari.
     * @return static
     */
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

    /**
     * State: sewa bulanan mulai tanggal tertentu (jumlah hari = jumlah hari
     * di bulan tanggal mulai, biaya = pengali bulanan x harga harian).
     *
     * @param  CarbonInterface  $start  Tanggal mulai.
     * @return static
     */
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

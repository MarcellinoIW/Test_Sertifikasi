<?php

namespace Database\Factories;

use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model RentalExtension. Default: perpanjangan 5 hari harian.
 *
 * @extends Factory<RentalExtension>
 */
class RentalExtensionFactory extends Factory
{
    /**
     * Definisi atribut default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id' => Rental::factory(),
            'admin_id' => User::factory()->admin(),
            'old_end_date' => today(),
            'new_end_date' => today()->addDays(5),
            'additional_days' => 5,
            'monthly_blocks' => 0,
            'daily_days' => 5,
            'cost' => 100000,
            'payment_proof' => 'payment-proofs/demo.png',
        ];
    }
}

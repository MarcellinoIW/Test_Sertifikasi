<?php

namespace Database\Factories;

use App\Models\Rental;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

// factory untuk membuat data perpanjangan palsu
class RentalExtensionFactory extends Factory
{
    // function untuk data default factory
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

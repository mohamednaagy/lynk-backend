<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancingOrder>
 */
class FinancingOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $created_at = $this->faker->dateTimeBetween(30);

        return [
            'reference_number' => $this->faker->randomNumber(9),
            'national_id' => $this->faker->randomNumber(9),
            'amount' => $this->faker->randomNumber(5),
            'selling_price' => $this->faker->randomNumber(5),
            'status' => $this->faker->numberBetween(1, 4),
            'approved_at' => $this->faker->dateTimeBetween(30),
            'reason' => $this->faker->randomLetter(),
            'created_at' => $created_at,
            'updated_at' => $created_at,
        ];
    }
}

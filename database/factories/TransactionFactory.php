<?php

namespace Database\Factories;

use Cknow\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker->unique()->uuid(),
            'reference_number' => $this->faker->unique()->uuid(),
            'amount' => Money::parseByDecimal($this->faker->randomNumber(), Money::getDefaultCurrency()),
            'meta' => ['meta' => $this->faker->text(100)],
        ];
    }
}

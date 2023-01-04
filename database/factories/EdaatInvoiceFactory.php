<?php

namespace Database\Factories;

use App\Models\EdaatInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EdaatInvoice>
 */
class EdaatInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $created_at = $this->faker->dateTimeBetween(30);

        return [
            'invoice_number' => $this->faker->randomNumber(9),
            'amount' => $this->faker->randomNumber(5),
            'status' => $this->faker->numberBetween(1, 3),
            'creator_id' => 1,
            'company_id' => 1,
            'created_at' => $created_at,
            'updated_at' => $created_at,
        ];
    }
}

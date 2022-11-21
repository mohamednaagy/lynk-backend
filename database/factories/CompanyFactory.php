<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'unique_name' => $this->faker->unique()->name(),
            'company_cr' => $this->faker->unique()->text(),
            'status' => $this->faker->numberBetween(1, 4),
            'public_status_comment' => $this->faker->boolean(),
            'internal_status_comment' => $this->faker->boolean(),
            'does_order_require_approval' => $this->faker->boolean(),
            'order_cost' => $this->faker->randomDigit(),
        ];
    }
}

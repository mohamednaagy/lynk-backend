<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
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
            'name' => $this->faker->company,
            'unique_name' => $this->faker->unique()->domainName,
            'company_cr' => $this->faker->unique()->text(100),
            'status' => 3,
            'public_status_comment' => $this->faker->randomLetter,
            'internal_status_comment' => $this->faker->randomLetter,
            'does_order_require_approval' => $this->faker->boolean,
            'order_cost' => $this->faker->randomDigitNotNull,
        ];
    }
}

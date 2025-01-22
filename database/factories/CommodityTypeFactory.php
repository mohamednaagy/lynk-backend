<?php

namespace Database\Factories;

use App\Enums\CommodityTypeStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommodityTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true), // Generate a random name
            'unique_name' => $this->faker->unique()->slug, // Generate a unique slug
            'description' => $this->faker->sentence, // Generate a random description
            'status' => $this->faker->randomElement([
                CommodityTypeStatus::Active,
                CommodityTypeStatus::Inactive,
            ]),
        ];
    }
}

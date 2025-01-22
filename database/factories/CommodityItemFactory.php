<?php

namespace Database\Factories;

use App\Models\CommodityType;
use App\Models\Currency;
use App\Models\Measurement;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommodityItemFactory extends Factory
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
            'company_id' => Supplier::factory(), // Assumes a Supplier factory exists
            'min_price' => $this->faker->randomFloat(2, 10, 100), // Random float between 10 and 100
            'max_price' => $this->faker->randomFloat(2, 101, 500), // Random float between 101 and 500
            'volume_sellable_unit' => $this->faker->randomNumber(3), // Random number up to 3 digits
            'currency_id' => Currency::factory(), // Assumes a Currency factory exists
            'measurement_id' => Measurement::factory(), // Assumes a Measurement factory exists
            'commodity_type_id' => CommodityType::factory(), // Assumes a CommodityType factory exists
        ];
    }
}

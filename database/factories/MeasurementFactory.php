<?php

namespace Database\Factories;

use App\Models\Measurement;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeasurementFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->randomElement(['Kilogram', 'Liter', 'Meter', 'Piece', 'Gram']), // Example measurement units
            'symbol' => $this->faker->randomElement(['kg', 'l', 'm', 'pc', 'g']), // Abbreviations for the units
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'subject' => $this->faker->randomLetter(),
            'body' => $this->faker->randomLetter(),
            'status' => $this->faker->numberBetween(1, 3),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->email(),
        ];
    }
}

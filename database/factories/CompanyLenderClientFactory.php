<?php

namespace Database\Factories;

use App\Enums\CompanyLenderClientType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class CompanyLenderClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'type' => $this->faker->randomElement(CompanyLenderClientType::getValues()),
            'national_id' => $this->faker->numerify('##########'),
            'company_id' => Company::factory(),
        ];
    }
}

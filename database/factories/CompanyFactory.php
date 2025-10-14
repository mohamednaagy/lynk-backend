<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
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
            'type' => CompanyType::Lender,
            'name' => $this->faker->company,
            'unique_name' => $this->faker->unique()->domainName,
            'status' => CompanyStatus::Approved,
        ];
    }
}

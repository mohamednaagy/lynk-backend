<?php

namespace Database\Factories;

use App\Enums\FinancingOrderBorrowerTypeEnum;
use App\Enums\FinancingOrderLenderTypeEnum;
use App\Enums\FinancingOrderTypeEnum;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancingOrder>
 */
class FinancingOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $created_at = $this->faker->dateTimeBetween(30);

        // Create or get a company if not provided
        $company = Company::first();
        if (! $company) {
            $company = Company::factory()->create();
        }

        return [
            'reference_number' => $this->faker->randomNumber(9),
            'national_id' => $this->faker->randomNumber(9),
            'amount' => $this->faker->randomNumber(5),
            'selling_price' => $this->faker->randomNumber(5),
            'status' => $this->faker->numberBetween(1, 4),
            'approved_at' => $this->faker->dateTimeBetween(30),
            'status_reason' => $this->faker->randomLetter(),
            'created_at' => $created_at,
            'updated_at' => $created_at,
            'company_id' => $company->id,
            'lender_type' => FinancingOrderLenderTypeEnum::NormalLending,
            'lender_identifier' => $company->id,
            'borrower_type' => FinancingOrderBorrowerTypeEnum::Lender,
            'type' => FinancingOrderTypeEnum::NormalLending,
        ];
    }
}

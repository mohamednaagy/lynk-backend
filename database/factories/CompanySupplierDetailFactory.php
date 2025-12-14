<?php

namespace Database\Factories;

use App\Enums\CommoitySupplierMarketType;
use App\Enums\CommoitySupplierStatus;
use App\Models\Company;
use App\Models\CompanySupplierDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanySupplierDetailFactory extends Factory
{
    protected $model = CompanySupplierDetail::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'description' => $this->faker->optional()->sentence,
            'market_type' => CommoitySupplierMarketType::Local(),
            'status' => CommoitySupplierStatus::Active(),
        ];
    }

    /**
     * Indicate that the supplier is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommoitySupplierStatus::Active(),
        ]);
    }

    /**
     * Indicate that the supplier is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommoitySupplierStatus::Inactive(),
        ]);
    }

    /**
     * Indicate the market type is local.
     */
    public function local(): static
    {
        return $this->state(fn (array $attributes) => [
            'market_type' => CommoitySupplierMarketType::Local(),
        ]);
    }
}

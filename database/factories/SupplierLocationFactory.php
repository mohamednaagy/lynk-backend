<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SupplierLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierLocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SupplierLocation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'unique_identifier' => $this->faker->unique()->domainName,
            'name' => $this->faker->company,
            'description' => $this->faker->sentence,
        ];
    }
}

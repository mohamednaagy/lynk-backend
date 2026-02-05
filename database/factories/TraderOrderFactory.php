<?php

namespace Database\Factories;

use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class TraderOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TraderOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'financing_order_id' => FinancingOrder::factory(),
            'status' => TraderOrderStatus::Initiated,
            'reference' => $this->faker->uuid,
            'provider' => 'Lynk', // Assuming a default provider
            'version' => 'v1',    // Assuming a default version
            'mode' => 'automatic', // Assuming a default mode
            'data' => [],         // Assuming default empty data
            'last_history_action' => null,
        ];
    }
}

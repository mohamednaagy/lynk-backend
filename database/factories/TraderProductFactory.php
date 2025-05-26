<?php

namespace Database\Factories;

use App\Enums\Trader;
use App\Enums\TraderProductStatus;
use App\Models\TraderProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class TraderProductFactory extends Factory
{
    protected $model = TraderProduct::class;

    public function definition(): array
    {
        return [
            'name' => [
                'en' => $this->faker->words(3, true),
                'ar' => $this->faker->words(3, true),
            ],
            'code' => $this->faker->unique()->regexify('[A-Z]{2}-[A-Z]{4}[0-9]{2}'),
            'status' => TraderProductStatus::Enabled,
            'provider' => Trader::Bursam,
            'order' => $this->faker->numberBetween(1, 100),
        ];
    }
}

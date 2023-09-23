<?php

namespace Database\Factories;

use App\Enums\WalletNotificationType;
use App\Models\WalletNotification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class WalletNotificationFactory extends Factory
{
    protected $model = WalletNotification::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(WalletNotificationType::getValues()),
            'value' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}

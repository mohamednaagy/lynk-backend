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
            'notified_at' => $this->faker->boolean ? now() : null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function notified()
    {
        return $this->state(function (array $attributes) {
            return [
                'notified_at' => now(),
            ];
        });
    }

    public function notNotified()
    {
        return $this->state(function (array $attribute) {
            return [
                'notified_at' => null,
            ];
        });
    }
}

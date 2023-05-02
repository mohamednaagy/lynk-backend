<?php

namespace Database\Factories;

use App\Enums\WebhookType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Webhook>
 */
class WebhookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'url' => 'http://localhost.com',
            'type' => WebhookType::OrderUpdates,
        ];
    }
}

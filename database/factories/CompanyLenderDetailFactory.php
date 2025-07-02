<?php

namespace Database\Factories;

use App\Enums\CompanyMarketType;
use App\Enums\TraderOrderMode;
use App\Models\Company; // Optional if you use enums
use App\Models\CompanyLenderDetail;   // Optional if you use enums
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyLenderDetailFactory extends Factory
{
    protected $model = CompanyLenderDetail::class;

    public function definition()
    {
        return [
            'company_id' => Company::factory(), // Creates a related company
            'company_cr' => $this->faker->unique()->bothify('CR-#######'),
            'contract_number' => $this->faker->unique()->bothify('CN-#######'),
            'notifications_email' => $this->faker->safeEmail,
            'preferred_market_type' => $this->faker->randomElement([
                CompanyMarketType::Local->value ?? 1,
                CompanyMarketType::International->value ?? 2,
            ]),
            'does_order_require_approval' => $this->faker->boolean,
            'notify_admins_about_new_orders' => $this->faker->boolean,
            'default_contract_sign_time_limit' => $this->faker->numberBetween(1, 48), // in hours
            'force_preferred_commodity_type' => $this->faker->boolean,
            'require_initiate_trade_request' => $this->faker->boolean,
            'internal_status_comment' => $this->faker->optional()->sentence,
            'public_status_comment' => $this->faker->optional()->sentence,
            'auto_complete_murabaha_order' => $this->faker->boolean,
            'trading_mode' => $this->faker->randomElement([
                TraderOrderMode::Automatic->value ?? 'automatic',
                TraderOrderMode::Manual->value ?? 'manual',
            ]),
            'force_unique_reference_number' => $this->faker->boolean,
            'webhook_secret_key' => $this->faker->optional()->sha256,
            'allow_preferred_commodity_in_order' => $this->faker->boolean,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

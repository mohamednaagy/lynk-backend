<?php

namespace Database\Factories;

use App\Models\CompanyLenderClient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class ClientAutoSellPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_lender_client_id' => CompanyLenderClient::factory(),
            'effective_start' => Carbon::now()->toDateString(),
            'effective_end' => Carbon::now()->toDateString(),
        ];
    }
}

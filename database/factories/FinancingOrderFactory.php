<?php

namespace Database\Factories;

use App\Enums\FinancingOrderBorrowerTypeEnum;
use App\Enums\FinancingOrderCancelReason;
use App\Enums\FinancingOrderLenderTypeEnum;
use App\Enums\FinancingOrderStatus;
use App\Enums\FinancingOrderTypeEnum;
use App\Models\Company;
use App\Models\FinancingOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinancingOrder>
 */
class FinancingOrderFactory extends Factory
{
    protected $model = FinancingOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
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
            'status_reason' => null,
            'created_at' => $created_at,
            'updated_at' => $created_at,
            'company_id' => $company->id,
            'lender_type' => FinancingOrderLenderTypeEnum::NormalLending,
            'lender_identifier' => $company->id,
            'borrower_type' => FinancingOrderBorrowerTypeEnum::Lender,
            'type' => FinancingOrderTypeEnum::NormalLending,
        ];
    }

    /**
     * Configure the factory so that orders with status_reason (rejected/cancelled) get a cancel detail.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (FinancingOrder $order): void {
            $comment = $order->getRawOriginal('status_reason') ?? $order->status_reason;
            if ($comment === null || $comment === '') {
                return;
            }
            $status = $order->status instanceof FinancingOrderStatus
                ? $order->status
                : FinancingOrderStatus::fromValue((int) $order->status);
            if (! in_array($status->value, [FinancingOrderStatus::Rejected, FinancingOrderStatus::Cancelled], true)) {
                return;
            }
            $creatorId = $order->creator_id ?? $order->getRawOriginal('creator_id');
            if (! $creatorId) {
                return;
            }
            $order->cancelDetail()->create([
                'creator_id' => $creatorId,
                'cancel_reason' => $status->value === FinancingOrderStatus::Rejected
                    ? FinancingOrderCancelReason::Rejected
                    : FinancingOrderCancelReason::Cancelled,
                'comment' => $comment,
            ]);
        });
    }
}

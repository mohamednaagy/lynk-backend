<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CreateTrading;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Exceptions\OrderIsAlreadyCompletedException;
use App\Exceptions\OrderIsAlreadyHasActiveTraderOrderException;
use App\Models\FinancingOrder;
use Illuminate\Support\Arr;

class CreateTradingAction implements CreateTrading
{
    /**
     * @return mixed
     */
    public function handle($orderId, array $data): void
    {
        $financingOrder = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if ($financingOrder->status->is(FinancingOrderStatus::Completed)) {
            throw new OrderIsAlreadyCompletedException;
        }

        if ($financingOrder->traderOrders()->whereIn('status', [
            TraderOrderStatus::InProgress,
            TraderOrderStatus::Completed,
        ])->exists()) {
            throw new OrderIsAlreadyHasActiveTraderOrderException;
        }

        $financingOrder->traderOrders()->create([
            'provider' => Arr::get($data, 'trader'),
            'reference' => Arr::get($data, 'reference_number'),
            'status' => TraderOrderStatus::InProgress,
        ]);
    }
}

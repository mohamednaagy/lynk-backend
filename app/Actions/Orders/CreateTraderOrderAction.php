<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CreateTraderOrder;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Exceptions\OrderAlreadyHasActiveTraderOrderException;
use App\Exceptions\OrderIsAlreadyCompletedException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Support\Arr;

class CreateTraderOrderAction implements CreateTraderOrder
{
    /**
     * @return mixed
     */
    public function handle($orderId, array $data): TraderOrder
    {
        $financingOrder = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($orderId);

        if ($financingOrder->status->is(FinancingOrderStatus::Completed)) {
            throw new OrderIsAlreadyCompletedException;
        }

        $doesInProgressTraderOrderExists = $financingOrder
            ->traderOrders()
            ->where('status', TraderOrderStatus::InProgress)
            ->exists();

        if ($doesInProgressTraderOrderExists) {
            throw new OrderAlreadyHasActiveTraderOrderException;
        }

        $traderOrder = $financingOrder->traderOrders()->create([
            'provider' => Arr::get($data, 'trader'),
            'reference' => Arr::get($data, 'reference_number'),
            'status' => TraderOrderStatus::InProgress,
        ]);

        $traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::GetTtiId,
        ]);

        return $traderOrder;
    }
}

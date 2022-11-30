<?php

namespace App\Support\Traders;

use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Database\Eloquent\Model;

trait TraderHelper
{
    public function createTraderOrder(FinancingOrder $financingOrder, string $ttiId, string $provider): Model|TraderOrder
    {
        return $financingOrder->traderOrders()->create([
            'provider' => $provider,
            'reference' => $ttiId,
            'status' => TraderOrderStatus::InProgress,
        ]);
    }

    public function updateOrderStatus($order, int $status): void
    {
        $order->update([
            'status' => $status,
        ]);
    }

    public function createTraderOrderHistory(TraderOrder $traderOrder, int $action): void
    {
        $traderOrder->traderHistories()->create([
            'action' => $action,
        ]);
    }
}

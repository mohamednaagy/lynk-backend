<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\CommodityPurchasedOrderStatus;
use App\Actions\Contracts\Orders\CommoditySoldToCustomerOrderStatus;
use App\Actions\Contracts\Orders\MurabahaSaleCompletedOrderStatus;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;

class FinancingOrderObserver
{
    /**
     * Handle the FinancingOrder "updated" event.
     *
     * @param  FinancingOrder  $financingOrder
     * @return void
     *
     * @throws \Exception
     */
    public function updated(FinancingOrder $financingOrder): void
    {
        $product = $financingOrder->activeTraderOrder()->first()->product ?? '';
        $quantity = $financingOrder->activeTraderOrder()->first()->quantity ?? '';

        match ($financingOrder->status->value) {
            FinancingOrderStatus::CommoditySoldToCustomer => app(CommoditySoldToCustomerOrderStatus::class)->handle($financingOrder, $product, $quantity),
            FinancingOrderStatus::MurabahaSaleCompleted => app(MurabahaSaleCompletedOrderStatus::class)->handle($financingOrder, $product, $quantity),
            FinancingOrderStatus::CommodityPurchased => app(CommodityPurchasedOrderStatus::class)->handle($financingOrder, $product, $quantity),
            default => null
        };
    }
}

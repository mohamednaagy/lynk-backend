<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\CancelOrder;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\OrderService;

class CancelOrderAction implements CancelOrder
{
    public function __construct() {}

    public function handle(LocalMarketOrder $localMarketOrder)
    {
        if ($localMarketOrder->canCancelledOrder()) {
            (new OrderService)->cancelOrder($localMarketOrder);
        }
    }
}

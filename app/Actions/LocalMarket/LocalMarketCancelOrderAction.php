<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\LocalMarketCancelOrder;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\OrderService;

class LocalMarketCancelOrderAction implements LocalMarketCancelOrder
{
    public function __construct() {}

    public function handle(LocalMarketOrder $localMarketOrder)
    {
        if ($localMarketOrder->canCancelledOrder()) {
            (new OrderService)->cancelOrder($localMarketOrder);
        }
    }
}

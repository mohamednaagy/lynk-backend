<?php

namespace App\Traits\LocalMarket;

use App\Models\LocalMarketOrder;

trait LocalMarketTrait
{
    protected function getLocalMarketOrderByReference($reference)
    {
        return LocalMarketOrder::where('external_order_no', $reference)->first();
    }
}

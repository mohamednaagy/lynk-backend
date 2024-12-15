<?php

namespace App\Actions\Contracts\LocalMarket;

use App\Models\LocalMarketOrder;

interface RequestDeliverProducts
{
    /**
     * Request delivery of products for a trader order.
     *
     * @param  LocalMarketOrder  $localMarketOrder  The local market order for which to request delivery
     */
    public function handle(LocalMarketOrder $localMarketOrder): void;
}

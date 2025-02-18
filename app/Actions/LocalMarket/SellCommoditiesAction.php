<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\SellCommodities;
use App\Enums\LocalMarket\OrderStatus;
use App\Traits\LocalMarket\LocalMarketTrait;

class SellCommoditiesAction implements SellCommodities
{
    use LocalMarketTrait;

    public function handle(string $reference): void
    {
        $localMarketOrder = $this->getLocalMarketOrderByReference($reference);
        $localMarketOrder->changeStatusTo(OrderStatus::PendingSellCommodities);
    }
}

<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\SellCommodities;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;

class SellCommoditiesAction implements SellCommodities
{
    public function __construct(
        LocalMarketOrder $localMarketOrder
    ) {}

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::PendingSellCommodities);
    }
}

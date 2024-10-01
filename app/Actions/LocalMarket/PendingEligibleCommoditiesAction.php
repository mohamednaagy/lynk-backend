<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Actions\Contracts\LocalMarket\PendingEligibleCommodities;
use App\Enums\LocalMarketOrderHistoryStatus;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;

class PendingEligibleCommoditiesAction implements PendingEligibleCommodities
{
    use LocalMarketHelperTrait;

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        $localMarketOrder->update(['status' => LocalMarketOrderStatus::PendingEligibleCommodities]);
        app(FindEligibleCommodities::class)->handle($localMarketOrder);
        $this->createLocalMarketOrderHistory($localMarketOrder, LocalMarketOrderHistoryStatus::PendingEligibleCommodities);
    }
}

<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Actions\Contracts\LocalMarket\PendingEligibleCommodities;
use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Support\Facades\Log;

class PendingEligibleCommoditiesAction implements PendingEligibleCommodities
{
    use LocalMarketHelperTrait;

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        $localMarketOrder->update(['status' => OrderStatus::PendingEligibleCommodities]);
        $this->createLocalMarketOrderHistory($localMarketOrder, OrderHistoryStatus::PendingEligibleCommodities);

        app(FindEligibleCommodities::class)->handle($localMarketOrder);

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('after find eligible commodities at PendingEligibleCommoditiesAction', $localMarketOrder), [
            'localMarketOrderId' => $localMarketOrder->id,
        ]);
    }
}

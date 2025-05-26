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
        $startTime = microtime(true);
        $localMarketOrder->update(['status' => OrderStatus::PendingEligibleCommodities]);
        $this->createLocalMarketOrderHistory($localMarketOrder, OrderHistoryStatus::PendingEligibleCommodities);

        app(FindEligibleCommodities::class)->handle($localMarketOrder);

        $duration = microtime(true) - $startTime;
        Log::channel('local_market')->info('PendingEligibleCommoditiesAction Duration', [
            'order_id' => $localMarketOrder->id,
            'duration' => convertMicrotimeToDuration($duration),
        ]);
    }
}

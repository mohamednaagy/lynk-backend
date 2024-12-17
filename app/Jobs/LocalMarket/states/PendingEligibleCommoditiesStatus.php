
<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;

class PendingEligibleCommoditiesStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(FindEligibleCommodities $getSuitableCommoditiesStocks): void
    {
        $this->localMarketOrder->update(['status' => OrderStatus::PendingEligibleCommodities]);
        $getSuitableCommoditiesStocks->handle($this->localMarketOrder);
        $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::PendingEligibleCommodities);
    }
}


<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use Illuminate\Support\Facades\Log;

class PendingEligibleCommoditiesStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(FindEligibleCommodities $getSuitableCommoditiesStocks): void
    {
        try {
            $this->localMarketOrder->update(['status' => OrderStatus::PendingEligibleCommodities]);
            $getSuitableCommoditiesStocks->handle($this->localMarketOrder);
            $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::PendingEligibleCommodities);
        } catch (\Exception $e) {
            Log::channel('local_market')->error("failed pending eligible cCommodities status, local market order id {$this->localMarketOrder->id}", ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}

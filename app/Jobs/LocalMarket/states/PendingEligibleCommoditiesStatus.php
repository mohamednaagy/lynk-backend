<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use App\Exceptions\LocalMarket\JobStatusException;

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
            throw new JobStatusException($e->getMessage(), 'failed pending eligible cCommodities status', $this->localMarketOrderID);
        }
    }
}

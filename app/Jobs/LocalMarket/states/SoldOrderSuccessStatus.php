<?php

namespace App\Jobs\LocalMarket\states;

use App\Jobs\LocalMarket\CommoditiesSettlement\ChangeStatus\SetCommoditiesSettlementStatusPending;

class SoldOrderSuccessStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->logQueueJob('Order Sold successfully');
        SetCommoditiesSettlementStatusPending::dispatch($this->localMarketOrderID);
    }
}

<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\CaseStatus;
use App\Enums\LocalMarket\OrderHistoryStatus;

class NoEligibleCommoditiesAvailableStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::NoEligibleCommoditiesAvailable);
        $data['case'] = CaseStatus::NoEligibleCommoditiesAvailable;
        $data['external_order_no'] = $this->localMarketOrder->external_order_no;
        $this->localMarketWebhook->with($data)->handle();
        $this->logQueueJob('Notify our customer sorry we can not find your eligible commodities');
    }
}

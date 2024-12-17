<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use App\Exceptions\LocalMarket\JobStatusException;

class NoEligibleCommoditiesAvailableStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::NoEligibleCommoditiesAvailable);
            $data['case'] = OrderStatus::NoEligibleCommoditiesAvailable;
            $data['external_order_no'] = $this->localMarketOrder->external_order_no;
            $this->localMarketWebhook->with($data)->handle();
            $this->logQueueJob('Notify our customer sorry we can not find your eligibilities commodities');
        } catch (\Exception $e) {
            throw new JobStatusException($e->getMessage(), 'failed no eligible commodities available status', $this->localMarketOrderID);
        }
    }
}

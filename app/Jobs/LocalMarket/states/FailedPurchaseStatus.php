<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use App\Exceptions\LocalMarket\JobStatusException;

class FailedPurchaseStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Nagy Continue this function
        // use webhook to notify the user
        try {
            $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::FailedPurchase);
            $this->localMarketWebhook->with(['case' => OrderStatus::FailedPurchase, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
            $this->logQueueJob('Sorry there is an error while purchasing commodities');
        } catch (\Exception $e) {
            throw new JobStatusException($e->getMessage(), 'failed purchase status', $this->localMarketOrderID);
        }
    }
}

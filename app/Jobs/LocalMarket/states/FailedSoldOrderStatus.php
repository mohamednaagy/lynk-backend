<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use App\Exceptions\LocalMarket\JobStatusException;

class FailedSoldOrderStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::FailedSell);
            $this->localMarketWebhook->with(['case' => OrderStatus::FailedSell, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
            $this->logQueueJob('Failed Order Sold successfully');
        } catch (\Exception $e) {
            throw new JobStatusException($e->getMessage(), 'failed sold order status', $this->localMarketOrderID);
        }
    }
}

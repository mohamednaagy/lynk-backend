<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;
use App\Exceptions\LocalMarket\JobStatusException;

class CancelledOrderStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->localMarketWebhook->with(['case' => OrderStatus::Cancelled, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
            $this->logQueueJob('Order cancelled successfully');
        } catch (\Exception $e) {
            throw new JobStatusException($e->getMessage(), 'failed cancelled order status', $this->localMarketOrderID);
        }
    }
}

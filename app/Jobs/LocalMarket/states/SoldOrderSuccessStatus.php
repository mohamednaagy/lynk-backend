<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;
use App\Exceptions\LocalMarket\JobStatusException;

class SoldOrderSuccessStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // handle rotation or complete order
            $this->localMarketWebhook->with(['case' => OrderStatus::CommoditiesSell, 'external_order_no' => $this->externalOrderNo])->handle();
            $this->logQueueJob('Order Sold successfully');
        } catch (\Exception $e) {
            throw new JobStatusException($e->getMessage(), 'failed sold order success status', $this->localMarketOrderID);
        }
    }
}

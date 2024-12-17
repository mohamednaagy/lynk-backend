<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;

class SoldOrderSuccessStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // handle rotation or complete order
        $this->localMarketWebhook->with(['case' => OrderStatus::CommoditiesSell, 'external_order_no' => $this->externalOrderNo])->handle();
        $this->logQueueJob('Order Sold successfully');
    }
}

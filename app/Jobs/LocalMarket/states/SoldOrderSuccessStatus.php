<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;
use App\Jobs\LocalMarket\CommoditiesSettlement\ChangeStatus\SetCommoditiesSettlementStatusPending;

class SoldOrderSuccessStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // handle rotation or complete order
        $this->localMarketWebhook->with(['case' => OrderStatus::CommoditiesSell, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        $this->logQueueJob('Order Sold successfully');
        SetCommoditiesSettlementStatusPending::dispatch($this->localMarketOrderID);
    }
}

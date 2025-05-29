<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;
use App\Jobs\LocalMarket\CommoditiesSettlement\ChangeStatus\SetCommoditiesSettlementStatusCanceled;

class CancelledOrderStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->localMarketWebhook->with(['case' => OrderStatus::Cancelled, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        $this->logQueueJob('Order cancelled successfully');
        SetCommoditiesSettlementStatusCanceled::dispatch($this->localMarketOrderID);
    }
}

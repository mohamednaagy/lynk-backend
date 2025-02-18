<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Enums\LocalMarket\OrderStatus;
use App\Jobs\LocalMarket\SellConfirmation\CheckOrderUnitOwnershipSellConfirmation;

class CommoditiesPurchaseCompletedStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->getDataOfLocalMarketOrder($this->localMarketOrder);
        $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::CommoditiesPurchased);
        $data['case'] = OrderStatus::CommoditiesPurchased;
        $data['external_order_no'] = $this->localMarketOrder->external_order_no;
        $this->localMarketWebhook->with($data)->handle();
        $this->logQueueJob('Congratulations Commodities purchased');

        // Dispatch a job to check the previous ownership of inventory units
        CheckOrderUnitOwnershipSellConfirmation::dispatch();
    }
}

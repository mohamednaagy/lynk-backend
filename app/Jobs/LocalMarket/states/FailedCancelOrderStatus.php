<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\CaseStatus;

class FailedCancelOrderStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->localMarketWebhook->with(['case' => CaseStatus::FailedToCancel, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        $this->logQueueJob('Failed to cancel order.');
    }
}

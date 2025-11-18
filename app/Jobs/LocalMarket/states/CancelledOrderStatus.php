<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\CaseStatus;

class CancelledOrderStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->localMarketWebhook->with(['case' => CaseStatus::Cancelled, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        $this->logQueueJob('Order cancelled successfully');
    }
}

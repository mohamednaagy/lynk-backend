<?php

namespace App\Jobs\LocalMarket\states;

class SoldOrderSuccessStatus extends BaseStatus
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->logQueueJob('Order Sold successfully');
    }
}

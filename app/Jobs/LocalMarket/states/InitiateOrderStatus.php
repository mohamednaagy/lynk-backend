<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\PendingEligibleCommodities;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class InitiateOrderStatus extends BaseStatus
{
    protected function setUp(): void
    {
        $this->onQueue('eligible_commodities_local_market');
        $this->logQueueJob();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(PendingEligibleCommodities::class)->handle($this->localMarketOrder);
        $this->logQueueJob('success initiate local market order step');
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->localMarketOrder->id;
    }
}

<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\BuyCommodities;
use App\Enums\LocalMarket\OrderHistoryStatus;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class EligibleCommoditiesFoundStatus extends BaseStatus
{
    protected function setUp(): void
    {
        $this->onQueue('buy_commodities_local_market_orders');
        $this->logQueueJob();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->logQueueJob('Start processing EligibleCommoditiesFoundStatus job');
        app(BuyCommodities::class)->handle($this->localMarketOrder);
        $this->logQueueJob('success buy commodity step');
        $this->createLocalMarketOrderHistory($this->localMarketOrder, OrderHistoryStatus::EligibleCommoditiesAvailable);
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

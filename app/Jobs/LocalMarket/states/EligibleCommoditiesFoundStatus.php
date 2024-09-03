<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\BuyCommodities;
use App\Enums\LocalMarketOrderHistoryStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EligibleCommoditiesFoundStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable , SerializesModels;

    public function __construct(private LocalMarketOrder $localMarketOrder)
    {
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(BuyCommodities $BuyCommodities): void
    {

        $BuyCommodities->handle(
            $this->localMarketOrder
        );

        $this->createLocalMarketOrderHistory($this->localMarketOrder, LocalMarketOrderHistoryStatus::EligibleCommoditiesAvailable);

    }
}

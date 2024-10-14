<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Enums\LocalMarketOrderHistoryStatus;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PendingEligibleCommoditiesStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable , SerializesModels;

    public function __construct(private LocalMarketOrder $localMarketOrder)
    {
        $this->onQueue('local_market');
        Log::channel('local_market')->info("add PendingEligibleCommoditiesStatus job to queue local_market with local market id {$this->localMarketOrder->id} ");
    }

    /**
     * Execute the job.
     */
    public function handle(FindEligibleCommodities $GetSuitableCommoditiesStocks): void
    {
        $this->localMarketOrder->update(['status' => LocalMarketOrderStatus::PendingEligibleCommodities]);
        $GetSuitableCommoditiesStocks->handle($this->localMarketOrder);
        $this->createLocalMarketOrderHistory($this->localMarketOrder, LocalMarketOrderHistoryStatus::PendingEligibleCommodities);
    }
}

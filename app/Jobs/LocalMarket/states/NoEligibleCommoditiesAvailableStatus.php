<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\LocalMarketOrderHistoryStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NoEligibleCommoditiesAvailableStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait , Queueable;

    public function __construct(private LocalMarketOrder $localMarketOrder)
    {
        $this->onQueue('local_market');
        Log::channel('local_market')->info("add NoEligibleCommoditiesAvailableStatus job to queue local_market with local market id {$this->localMarketOrder->id} ");
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Nagy Continue this function
        $data['external_order_no'] = $this->localMarketOrder->external_order_no;
        $data['case'] = 'NoEligibleCommoditiesAvailable';

        $this->createLocalMarketOrderHistory($this->localMarketOrder, LocalMarketOrderHistoryStatus::NoEligibleCommoditiesAvailable);
        app(LocalMarketWebhook::class)->handle($data);
        Log::channel('local_market')->info('Notify our customer sorry we can not find your eligibilities commodities ');
    }
}

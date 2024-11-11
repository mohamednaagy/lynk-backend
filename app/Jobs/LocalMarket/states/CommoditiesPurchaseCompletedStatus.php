<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
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

class CommoditiesPurchaseCompletedStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable , SerializesModels;

    private LocalMarketWebhook $localMarketWebhook;

    public function __construct(
        private LocalMarketOrder $localMarketOrder
    ) {
        $this->localMarketWebhook = app(LocalMarketWebhook::class);
        $this->onQueue('local_market');
        Log::channel('local_market')->info("add CommoditiesPurchaseCompletedStatus job to queue local_market with local market id {$this->localMarketOrder->id} ");
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->getDataOfLocalMarketOrder($this->localMarketOrder);
        $this->createLocalMarketOrderHistory($this->localMarketOrder, LocalMarketOrderHistoryStatus::CommoditiesPurchased);
        $data['case'] = LocalMarketOrderStatus::CommoditiesPurchased;
        $data['external_order_no'] = $this->localMarketOrder->external_order_no;
        $this->localMarketWebhook->with($data)->handle();
        Log::channel('local_market')->info("Congratulations Commodities purchased for order {$this->localMarketOrder->id}");
    }
}

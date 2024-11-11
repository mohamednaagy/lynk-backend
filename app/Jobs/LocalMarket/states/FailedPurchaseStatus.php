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
use Illuminate\Support\Facades\Log;

class FailedPurchaseStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait , Queueable;

    private LocalMarketWebhook $localMarketWebhook;

    public function __construct(
        private LocalMarketOrder $localMarketOrder
    ) {
        $this->localMarketWebhook = app(LocalMarketWebhook::class);
        $this->onQueue('local_market');
        Log::channel('local_market')->info("add FailedPurchaseStatus job to queue local_market with local market id {$this->localMarketOrder->id} ");
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Nagy Continue this function
        // use webhook to notify the user

        $this->createLocalMarketOrderHistory($this->localMarketOrder, LocalMarketOrderHistoryStatus::FailedPurchase);
        $this->localMarketWebhook->with(['case' => LocalMarketOrderStatus::FailedPurchase, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        Log::channel('local_market')->info('Sorry there is an error while purchasing commodities for order ');

    }
}

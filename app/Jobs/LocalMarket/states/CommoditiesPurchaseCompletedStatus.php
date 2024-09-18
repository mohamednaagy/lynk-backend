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
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CommoditiesPurchaseCompletedStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable , SerializesModels;

    public function __construct(private LocalMarketOrder $localMarketOrder)
    {
        $this->onQueue('local_market');
        Log::channel('local_market')->info("add CommoditiesPurchaseCompletedStatus job to queue local_market with local market id {$this->localMarketOrder->id} ");
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->getDataOfLocalMarketOrder($this->localMarketOrder);
        $data['case'] = 'CommoditiesPurchased';
        $this->createLocalMarketOrderHistory($this->localMarketOrder, LocalMarketOrderHistoryStatus::CommoditiesPurchased);
        app(LocalMarketWebhook::class)->handle($data);
        Log::channel('local_market')->info("Congratulations Commodities purchased for order {$this->localMarketOrder->id}");
    }
}

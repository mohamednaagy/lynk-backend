<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SoldOrderSuccessStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable, SerializesModels;

    private LocalMarketWebhook $localMarketWebhook;

    public function __construct(
        private LocalMarketOrder $localMarketOrder
    ) {

        $this->localMarketWebhook = app(LocalMarketWebhook::class);

        $this->onQueue('local_market');
        $this->logQueueJob();
    }

    public function handle(): void
    {

        $this->localMarketWebhook->with(['case' => LocalMarketOrderStatus::CommoditiesSell, 'external_order_no' => $this->localMarketOrder->external_order_no])->handle();
        $this->logQueueJob('Order Sold successfully');
    }

    private function logQueueJob(?string $message = 'Sold order status job added to queue local_market'): void
    {
        Log::channel('local_market')->info(
            "$message",
            ['order_id' => $this->localMarketOrder->id]
        );
    }
}

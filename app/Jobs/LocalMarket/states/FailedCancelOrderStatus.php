<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FailedCancelOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable, SerializesModels;

    private LocalMarketWebhook $localMarketWebhook;

    public function __construct(
        private LocalMarketOrder $localMarketOrder
    ) {
        $this->onQueue('local_market');
    }

    public function handle(): void
    {
        Log::channel('local_market')->info('Failed to cancel order.');
    }
}

<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Models\LocalMarketOrder;
use App\Models\TraderOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class FailedPurchaseStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(private LocalMarketOrder $localMarketOrder)
    {
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Nagy Continue this function
        // use webhook to notify the user
        $traderOrder = TraderOrder::lockForUpdate()->where('reference', $this->localMarketOrder->reference)->firstOrFail();
        $data = [
            'case' => 'FailedPurchase',
            'products' => [],
        ];
        app(LocalMarketWebhook::class)->handle($traderOrder, $data);
        Log::info('Sorry there is an error while purchasing commodities for order ');
    }
}

<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\TraderOrderMode;
use App\Models\FinancingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDailySellingPendingCommodityToMarket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->onQueue('bursam');
        Log::channel(LOG_CHANNEL_BURSAM)->info('ProcessDailySellingPendingCommodityToMarket: Job constructor');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        log::channel(LOG_CHANNEL_BURSAM)->info('Starting ProcessDailySellingPendingCommodityToMarket Job');

        FinancingOrder::query()
            ->whereHas('activeTraderOrder', function (Builder $query) {
                return $query->where('provider', 'bursam')
                    ->where('version', 'v2')
                    ->where('mode', TraderOrderMode::Automatic);
            })
            ->select('id')
            ->lazyById()
            ->each(function (FinancingOrder $financingOrder) { 
                log::channel(LOG_CHANNEL_BURSAM)->info('fire auto cancel job for financing_order_id => ' . $financingOrder->id);
                ProcessBursamCancelTimeOutOrder::dispatch($financingOrder);
            });
    }

    public function failed($exception)
    {
        log::channel(LOG_CHANNEL_BURSAM)->error('ProcessDailySellingPendingCommodityToMarket', ['message' => $exception->getMessage() , 'trace' => $exception->getTraceAsString()]);
    }
}

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
        Log::channel('bursam')->info('ProcessDailySellingPendingCommodityToMarket: Job constructor']);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('bursam')->info('Starting ProcessDailySellingPendingCommodityToMarket Job');

        FinancingOrder::query()
            ->whereHas('activeTraderOrder', function (Builder $query) {
                return $query->where('provider', 'bursam')
                    ->where('version', 'v2')
                    ->where('mode', TraderOrderMode::Automatic);
            })
            ->select('id')
            ->lazyById()
            ->each(function (FinancingOrder $financingOrder) {
                Log::channel('bursam')->info("fire auto cancel job for finance order {$financingOrder->id}");
                ProcessBursamCancelTimeOutOrder::dispatch($financingOrder);
            });
    }

    public function failed($exception)
    {
        Log::channel('bursam')->error('ProcessDailySellingPendingCommodityToMarket', ['message' => $exception->getMessage()]);
    }
}

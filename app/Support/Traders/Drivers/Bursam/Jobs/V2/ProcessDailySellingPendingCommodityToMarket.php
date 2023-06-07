<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        FinancingOrder::query()
            ->whereHas('activeTraderOrder', function ($query) {
                return $query->where('provider', 'bursam')
                    ->where('version', 'v2');
            })
            ->select('id')
            ->lazyById()
            ->each(function (FinancingOrder $financingOrder) {
                DB::transaction(function () use ($financingOrder) {
                    $lockedFinancingOrder = FinancingOrder::query()->lockForUpdate($financingOrder->id);

                    $lockedFinancingOrder->activeTraderOrder->each(function ($activeTraderOrder) {
                        Trader::driver($activeTraderOrder->provider, $activeTraderOrder->version)
                            ->cancelTraderOrder($activeTraderOrder);
                    });
                });
            });
    }
}

<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $activeFinancingOrders = FinancingOrder::query()
            ->whereHas('activeTraderOrder', function ($query) {
                return $query->where('provider', 'bursam')
                    ->where('version', 'v2');
            })
            ->get();

        $activeFinancingOrders->each(function ($financingOrder) {
            $financingOrder->activeTraderOrder->each(function ($activeTraderOrder) use ($financingOrder) {
                Trader::driver($activeTraderOrder->provider, $activeTraderOrder->version)
                    ->cancelOrder($financingOrder);
            });
        });
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}

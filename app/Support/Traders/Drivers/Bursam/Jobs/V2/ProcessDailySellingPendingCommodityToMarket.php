<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\TraderOrderMode;
use App\Models\FinancingOrder;
use Carbon\Carbon;
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
        $this->onQueue('bursam');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        FinancingOrder::query()
            ->whereHas('activeTraderOrder', function ($query) {
                return $query->where('provider', 'bursam')
                    ->where('version', 'v2')
                    ->where('mode', TraderOrderMode::Automatic)
                    ->whereDate('created_at', Carbon::today());
            })
            ->select('id')
            ->lazyById()
            ->each(function (FinancingOrder $financingOrder) {
                ProcessBursamCancelTimeOutOrder::dispatch($financingOrder);
            });
    }
}

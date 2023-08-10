<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InitiateTraderOrdersIfTimedOut implements ShouldQueue
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
            ->whereHas('latestTraderOrder', function ($query) {
                return $query->where('status', TraderOrderStatus::Cancelled)
                    ->where('data->cancel_reason', TraderOrderCancelReason::MurabhaTimeout)
                    ->where('provider', 'bursam')
                    ->where('version', 'v2')
                    ->whereDate('created_at', now()->toDateString());
            })
            ->select('id')
            ->lazyById()
            ->each(function (FinancingOrder $financingOrder) {
                ProcessBursamInitiateTraderOrder::dispatch($financingOrder);
            });
    }
}

<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrder;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDailySoldCommodityToMarket implements ShouldQueue
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
    public function handle(InitiateTraderOrder $initiateTraderOrder)
    {
        FinancingOrder::query()
            ->whereHas('traderOrders', function ($query) {
                return $query->where('status', TraderOrderStatus::Cancelled)
                    ->where('cancel_reason', TraderOrderCancelReason::MurabhaTimeout)
                    ->where('provider', 'bursam')
                    ->where('version', 'v2');
            })
            ->select('id')
            ->lazyById()
            ->each(function (FinancingOrder $financingOrder) use ($initiateTraderOrder) {
                try {
                    DB::transaction(function () use ($initiateTraderOrder, $financingOrder) {
                        $initiateTraderOrder->handle($financingOrder->id);
                    });
                } catch (\Throwable $th) {
                    Log::error($th->getMessage(), ['exception' => $th]);
                }
            });
    }
}

<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\TraderOrderCancelReason;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessBursamCancelTimeOutOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected FinancingOrder $financingOrder)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::transaction(function () {
            $lockedFinancingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder->id);

            $lockedFinancingOrder->activeTraderOrder->each(function ($activeTraderOrder) {
                Trader::driver($activeTraderOrder->provider, $activeTraderOrder->version)
                    ->cancelTraderOrder($activeTraderOrder, TraderOrderCancelReason::MurabhaTimeout);
            });
        });
    }
}

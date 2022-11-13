<?php

namespace App\Jobs;

use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccClientWakalaCompletedOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $financingOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($financingOrder)
    {
        $this->financingOrder = $financingOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder);
            if ($financingOrder->traderOrders()->whereIn('status', [
                TraderOrderStatus::InProgress,
                TraderOrderStatus::Completed,
            ])->count() > 0) {
                return;
            }

            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->getTtiId($financingOrder);

            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->updateOrderStatus($financingOrder, FinancingOrderStatus::WaitingPurchasingCommodity);
        });
    }
}

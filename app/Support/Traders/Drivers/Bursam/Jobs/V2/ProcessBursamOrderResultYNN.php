<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessBursamOrderResultYNN implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrder)
    {
        //
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrder;
    }

    public function backoff(): int
    {
        // Wait 30 minutes between retries
        return config('trader.providers.bursam.purchasing_commodity_job_backoff_time');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $traderOrder = TraderOrder::query()->lockForUpdate()->findOrFail($this->traderOrder);

        if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)) {
            return;
        }

        Trader::driver('bursam', $traderOrder->version)->fetchOrderResultYNN($traderOrder);
    }

    public function failed($exception)
    {
        $traderOrder = TraderOrder::query()->lockForUpdate()->findOrFail($this->traderOrder);
        $financingOrder = $traderOrder->order;
        DB::transaction(function () use ($financingOrder, $traderOrder) {
            $financingOrder->update([
                'status' => FinancingOrderStatus::PendingApproval,
            ]);

            $traderOrder->update([
                'status' => TraderOrderStatus::PurchasingFailure,
            ]);
        });
    }
}

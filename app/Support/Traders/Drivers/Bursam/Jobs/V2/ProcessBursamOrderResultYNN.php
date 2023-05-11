<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessBursamOrderResultYNN implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    public int $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
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
            $traderOrder = TraderOrder::query()
                ->lockForUpdate()
                ->findOrFail($this->traderOrderId);

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)) {
                return;
            }

            Trader::driver('bursam', $traderOrder->version)->fetchOrderResultYNN($traderOrder);
        });
    }

    public function failed($exception)
    {
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->lockForUpdate()
                ->findOrFail($this->traderOrderId);

            $traderOrder->order->update([
                'status' => FinancingOrderStatus::PendingApproval,
            ]);

            $traderOrder->update([
                'status' => TraderOrderStatus::PurchasingFailure,
            ]);
        });
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }

    public function backoff(): int
    {
        return config('trader.providers.bursam.purchasing_commodity_job_backoff_time');
    }
}

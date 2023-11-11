<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessBursamOrderResultNYY implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('bursam');
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->where('status', TraderOrderStatus::InProgress)
                ->lockForUpdate()
                ->find($this->traderOrderId);

            if (
                is_null($traderOrder)
                || ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument)
            ) {
                return;
            }

            Trader::driver('bursam', $traderOrder->version)->fetchOrderResultNYY($traderOrder);
        });
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(30);
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }
}

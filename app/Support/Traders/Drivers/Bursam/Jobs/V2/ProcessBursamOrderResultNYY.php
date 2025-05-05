<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBursamOrderResultNYY implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

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
        $traderOrder = TraderOrder::query()
            ->where('status', TraderOrderStatus::InProgress)
            ->find($this->traderOrderId);

        if (
            is_null($traderOrder)
            || ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument)
        ) {
            return;
        }

        Trader::driver('bursam', $traderOrder->version)->fetchOrderResultNYY($traderOrder);
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }

    public function failed($exception)
    {
        Log::channel('bursam')->error('ProcessBursamOrderResultNYY', ['traderOrderId' => $this->traderOrderId,  'message' => $exception->getMessage()]);
    }

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(5);
    }

    public function backoff(): array
    {
        return [60, 120, 120];
    }
}

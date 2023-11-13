<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderErrorCode;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
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
use Illuminate\Support\Facades\Log;

class ProcessBursamOrderResultYNN implements ShouldBeUnique, ShouldQueue
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
                ->whereIn('status', [TraderOrderStatus::InProgress, TraderOrderStatus::Initiated])
                ->lockForUpdate()
                ->find($this->traderOrderId);

            if (
                is_null($traderOrder)
                || ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)
            ) {
                return;
            }

            try {
                Trader::driver('bursam', $traderOrder->version)->fetchOrderResultYNN($traderOrder);
            } catch (TraderException $exception) {
                if ($exception->getContext('failure_code') == TraderErrorCode::INSUFFICIENT_COMMODITY) {
                    $traderOrder->order->update([
                        'status' => FinancingOrderStatus::TradingFailure,
                    ]);

                    $traderOrder->update([
                        'status' => TraderOrderStatus::Cancelled,
                        'failure_reason' => $exception->getContext('failure_reason'),
                        'cancel_reason' => TraderOrderCancelReason::FailureToPurchase,
                    ]);

                    $this->delete();
                } else {
                    Log::error($exception->getMessage(), $exception->getContext());
                    $this->fail($exception);
                }
            }
        });
    }

    public function failed($exception)
    {
        DB::transaction(function () use ($exception) {
            $traderOrder = TraderOrder::query()
                ->lockForUpdate()
                ->find($this->traderOrderId);

            if ($traderOrder === null) {
                return;
            }

            $traderOrder->order->update([
                'status' => FinancingOrderStatus::TradingFailure,
            ]);

            $traderOrder->update([
                'status' => TraderOrderStatus::Cancelled,
                'failure_reason' => method_exists($exception, 'getContext') ?
                    $exception->getContext('failure_reason')
                    : '',
                'cancel_reason' => TraderOrderCancelReason::FailureToPurchase,
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

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(30);
    }

    public function backoff(): int
    {
        return config('trader.providers.bursam.purchasing_commodity_job_backoff_time');
    }
}

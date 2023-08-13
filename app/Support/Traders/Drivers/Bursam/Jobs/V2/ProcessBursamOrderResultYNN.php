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
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessBursamOrderResultYNN implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

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
            logs()->debug('Test', ['hi0']);
            $traderOrder = TraderOrder::query()
                ->whereIn('status', [TraderOrderStatus::InProgress, TraderOrderStatus::Initiated])
                ->lockForUpdate()
                ->findOrFail($this->traderOrderId);

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)) {
                return;
            }

            logs()->debug('Test', ['hi']);
            try {
                Trader::driver('bursam', $traderOrder->version)->fetchOrderResultYNN($traderOrder);
            } catch (TraderException $exception) {
                logs()->debug('TraderException-0', [$exception]);
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
                    throw $exception;
                }
            }
        });
    }

    public function failed($exception)
    {
        logs()->debug('failed-logs', [$exception]);
        if ($exception instanceof TraderException) {
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
                    'failure_reason' => $exception->getContext('failure_reason'),
                    'cancel_reason' => TraderOrderCancelReason::FailureToPurchase,
                ]);
            });
        }
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

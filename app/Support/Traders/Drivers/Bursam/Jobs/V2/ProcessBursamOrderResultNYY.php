<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\FinancingOrderHistory;
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
use Illuminate\Support\Facades\Log;

class ProcessBursamOrderResultNYY implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    public $tries = 10;

    public $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('bursam');
        Log::channel(LOG_CHANNEL_BURSAM)->info('ProcessBursamOrderResultNYY: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        $traderOrder = TraderOrder::query()
            ->find($this->traderOrderId);

        if (is_null($traderOrder)) {
            log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamOrderResultNYY Job - not found trader_order_id:'.$this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId,
            ]);

            return;
        }

        if ($traderOrder->status->isNot(TraderOrderStatus::InProgress) && $traderOrder->status->isNot(TraderOrderStatus::PendingCancellation)) {
            Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in progress or pending cancellation in ProcessBursamOrderResultNYY job', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrderId,
                'status' => $traderOrder->status->value,
            ]);

            return;
        }

        if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument) && $traderOrder->status->is(TraderOrderStatus::InProgress)) {
            log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at ProcessBursamOrderResultNYY Job - incorrect action state', $traderOrder), [
                'financingOrderId' => $traderOrder?->order?->id,
                'traderOrderId' => $this->traderOrderId,
                'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                'expected_action' => FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            ]);

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
        log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamOrderResultNYY Job - trader_order_id => '.$this->traderOrderId, ['traderOrderId' => $this->traderOrderId,  'message' => $exception->getMessage(), 'line' => $exception->getLine(), 'file' => $exception->getFile(), 'trace' => $exception->getTraceAsString()]);
        $traderOrder = TraderOrder::query()->find($this->traderOrderId);
        if ($traderOrder) {
            $traderOrder->update([
                'status' => TraderOrderStatus::FailureToCancel,
            ]);
        }

    }
}

<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBursamGenerateClientWakala implements ShouldQueue
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
        Log::channel(LOG_CHANNEL_BURSAM)->info('bursa purchasing step => ProcessBursamGenerateClientWakala: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $traderOrder = null;
        try {
            // Get attempt count from job properties
            log::channel(LOG_CHANNEL_BURSAM)->info('start ProcessBursamGenerateClientWakala Job - trader_order_id => ' . $this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId
            ]);

            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                log::channel(LOG_CHANNEL_BURSAM)->info('error at ProcessBursamGenerateClientWakala Job - not found trader_order_id => ' . $this->traderOrderId, [
                    'traderOrderId' => $this->traderOrderId,
                    'timestamp' => saudi_now(),
                ]);
                return;
            }

            if ($traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in progress in ProcessBursamGenerateClientWakala job', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'status' => $traderOrder->status->value
                ]);
                return;
            }

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)) {
                Log::channel(LOG_CHANNEL_BURSAM)->warning('bursa purchasing step => ProcessBursamGenerateClientWakala: traderOrderId: '.$this->traderOrderId.' - Job skipped - incorrect action state', [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                    'expected_action' => FinancingOrderHistory::CreateTransferOwnershipToLenderDocument
                ]);

                return;
            }
        } catch (Throwable $e) {
            log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamGenerateClientWakala Job - retry wakala exception on attempt trader_order_id => ' . $this->traderOrderId, [
                'financingOrderId' => $traderOrder?->order?->id ?? null,
                'traderOrderId' => $this->traderOrderId,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
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

    public function failed($exception)
    {

        try {
            $traderOrder = TraderOrder::query()->find($this->traderOrderId);
            log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at ProcessBursamGenerateClientWakala Job - failed to generate client wakala - cancelling order', $traderOrder), [
                'financingOrderId' => $traderOrder?->order?->id,
                'traderOrderId' => $this->traderOrderId,
                'message' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'timestamp' => saudi_now(),
            ]);
            app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
            app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
        } catch (\Exception $e) {
            log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamGenerateClientWakala Job - failed to handle job failure trader_order_id => ' . $this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'original_error' => $exception->getMessage(),
                'timestamp' => saudi_now(),
            ]);
        }
    }
}

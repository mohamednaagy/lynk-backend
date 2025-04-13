<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
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

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $traderOrder = TraderOrder::query()
                ->where('status', TraderOrderStatus::InProgress)
                //->lockForUpdate()
                ->find($this->traderOrderId);

            if (
                is_null($traderOrder)
                || ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)
            ) {
                return;
            }

            Log::channel('bursam')->info('Processing client wakala generation', [
                'action' => 'start',
                'financing_order_id' => $traderOrder->order->id,
                'trader_order_id' => $this->traderOrderId,
                'timestamp' => saudi_now(),
            ]);
            app(GenerateClientWakala::class)->handle($traderOrder);

            Log::channel('bursam')->info('Successfully generated client wakala', [
                'action' => 'complete',
                'financing_order_id' => $traderOrder->order->id,
                'trader_order_id' => $this->traderOrderId,
                'timestamp' => saudi_now(),
            ]);
        } catch (\Exception $e) {
            Log::channel('bursam')->error('Failed to generate client wakala', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'financing_order_id' => $traderOrder->order->id ?? null,
                'trader_order_id' => $this->traderOrderId,
                'timestamp' => saudi_now(),
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
            Log::channel('bursam')->error('Failed to generate client wakala - cancelling order', [
                'error_message' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'financing_order_id' => $traderOrder->order->id,
                'trader_order_id' => $this->traderOrderId,
                'timestamp' => saudi_now(),
            ]);
            app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
            app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
        } catch (\Exception $e) {
            Log::channel('bursam')->error('Failed to handle job failure', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'original_error' => $exception->getMessage(),
                'trader_order_id' => $this->traderOrderId,
                'timestamp' => saudi_now(),
            ]);
        }
    }
}

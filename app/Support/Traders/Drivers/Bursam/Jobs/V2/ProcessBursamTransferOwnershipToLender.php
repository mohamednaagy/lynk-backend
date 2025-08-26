<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Console\Commands\RunHoldTraderWhenMarketOpenCommand;
use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBursamTransferOwnershipToLender implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    public $tries = 10;

    public $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId) {
        $this->afterCommit = true;
        Log::channel(LOG_CHANNEL_BURSAM)->info('bursa purchasing step => ProcessBursamTransferOwnershipToLender: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function handle()
    {
        log::channel(LOG_CHANNEL_BURSAM)->info('we will fire ProcessBursamTransferOwnershipToLender Job trader_order_id => '. $this->traderOrderId, [
            'traderOrderId' => $this->traderOrderId,
            'timestamp' => saudi_now(),
        ]);
        $traderOrder = null;
        try {

            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamTransferOwnershipToLender Job - not found trader order id:' . $this->traderOrderId, [
                    'traderOrderId' => $this->traderOrderId,
                ]);
                return;
            }


            if($traderOrder->status->isNot(TraderOrderStatus::InProgress)){
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in progress in ProcessBursamTransferOwnershipToLender job', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId ,
                    'status' => $traderOrder->status->value]);
                return;
            }

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachTtiHoldingCertificateDocument)) {
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa purchasing step => ProcessBursamTransferOwnershipToLender: traderOrderId: '.$this->traderOrderId.' - Job skipped - incorrect action state', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId ,
                    'expected_action' => FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                ]);
                return;
            }

            // Attempt to create transfer ownership document
            Trader::driver('bursam', $traderOrder->version)
                ->createTransferOwnershipToLenderDocument($traderOrder);

            // Run market open check
            (new RunHoldTraderWhenMarketOpenCommand)->handle();

            // Dispatch next step job
            ProcessBursamGenerateClientWakala::dispatch($this->traderOrderId);

            log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('Successfully processed transfer ownership to lender', $traderOrder), [
                'financingOrderId' => $traderOrder?->order?->id,
                'traderOrderId' => $this->traderOrderId,
                'action' => 'complete',
                'timestamp' => saudi_now(),
            ]);

        } catch (Throwable $e) {
            // Use a specific error channel/identifier for retry exceptions

            log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('Retry exception on attempt ', $traderOrder), [
                'financingOrderId' => $traderOrder?->order?->id ?? null,
                'traderOrderId' => $this->traderOrderId,
                'actual_exception' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'max_attempts' => $this->tries,
                'next_retry_after' => $this->backoff.' seconds',
                'timestamp' => saudi_now(),
            ]);

            // Rethrow the exception to trigger Laravel's retry mechanism
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

    // we commented this function due to unexpected call for it when we have a lot of orders
    // need to revisit and move it to the StopsTraderOrderOnJobFailure trait

    // public function failed($exception)
    // {
    // try {
    //     $traderOrder = TraderOrder::query()->find($this->traderOrderId);

    //     log::channel(LOG_CHANNEL_BURSAM)->error('Failed to process transfer ownership to lender - cancelling order', [
    //         'final_exception' => $exception->getMessage(),
    //         'error_code' => $exception->getCode(),
    //         'file' => $exception->getFile(),
    //         'line' => $exception->getLine(),
    //         'exception_class' => get_class($exception),
    //         'financing_order_id' => $traderOrder->order->id ?? null,
    //         'trader_order_id' => $this->traderOrderId,
    //         'max_attempts' => $this->tries,
    //         'timestamp' => saudi_now(),
    //     ]);

    //     if ($traderOrder) {
    //         app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
    //         app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
    //         (new RunHoldTraderWhenMarketOpenCommand)->handle();
    //     }

    // } catch (Throwable $e) {
    //     log::channel(LOG_CHANNEL_BURSAM)->error('Failed to handle job failure', [
    //         'error_message' => $e->getMessage(),
    //         'error_code' => $e->getCode(),
    //         'file' => $e->getFile(),
    //         'line' => $e->getLine(),
    //         'trace' => $e->getTraceAsString(),
    //         'original_error' => $exception->getMessage(),
    //         'trader_order_id' => $this->traderOrderId,
    //         'timestamp' => saudi_now(),
    //     ]);
    // }
    // }
}

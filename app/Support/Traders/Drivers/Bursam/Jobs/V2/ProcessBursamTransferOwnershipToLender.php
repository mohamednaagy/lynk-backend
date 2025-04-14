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
use Carbon\Carbon;
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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId) {}

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function handle()
    {
        $traderOrder = null;

        try {
            // Get attempt count from job properties
            $attemptNumber = $this->job->attempts();

            Log::channel('bursam')->info("Job attempt #{$attemptNumber} started", [
                'job_id' => $this->job ? $this->job?->getJobId() : 'unknown',
                'trader_order_id' => $this->traderOrderId,
                'timestamp' => saudi_now(),
            ]);

            $traderOrder = TraderOrder::query()
                ->where('status', TraderOrderStatus::InProgress)
                ->lockForUpdate()
                ->find($this->traderOrderId);

            if (
                is_null($traderOrder)
                || ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachTtiHoldingCertificateDocument)
            ) {
                Log::channel('bursam')->info('Job skipped - order not found or incorrect action state', [
                    'job_id' => $this->job ? $this->job?->getJobId() : 'unknown',
                    'trader_order_id' => $this->traderOrderId,
                    'attempt' => $attemptNumber,
                    'timestamp' => saudi_now(),
                ]);

                return;
            }

            Log::channel('bursam')->info('Processing transfer ownership to lender', [
                'action' => 'start',
                'job_id' => $this->job ? $this->job?->getJobId() : 'unknown',
                'financing_order_id' => $traderOrder?->order?->id,
                'trader_order_id' => $this->traderOrderId,
                'attempt' => $attemptNumber,
                'timestamp' => saudi_now(),
            ]);

            // Attempt to create transfer ownership document
            Trader::driver('bursam', $traderOrder->version)
                ->createTransferOwnershipToLenderDocument($traderOrder);

            // Run market open check
            (new RunHoldTraderWhenMarketOpenCommand)->handle();

            // Dispatch next step job
            ProcessBursamGenerateClientWakala::dispatch($this->traderOrderId);

            Log::channel('bursam')->info('Successfully processed transfer ownership to lender', [
                'action' => 'complete',
                'job_id' => $this->job ? $this->job?->getJobId() : 'unknown',
                'financing_order_id' => $traderOrder?->order?->id,
                'trader_order_id' => $this->traderOrderId,
                'attempt' => $attemptNumber,
                'timestamp' => saudi_now(),
            ]);

        } catch (Throwable $e) {
            // Use a specific error channel/identifier for retry exceptions
            $currentAttempt = $this->job->attempts();

            Log::channel('bursam')->error("Retry exception on attempt #{$currentAttempt}", [
                'actual_exception' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'job_id' => $this->job ? $this->job?->getJobId() : 'unknown',
                'trader_order_id' => $this->traderOrderId,
                'financing_order_id' => $traderOrder?->order?->id ?? null,
                'attempt' => $currentAttempt,
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

    public function failed($exception)
    {
        try {
            $traderOrder = TraderOrder::query()->find($this->traderOrderId);
            $finalAttempt = $this->job->attempts();

            Log::channel('bursam')->error('Failed to process transfer ownership to lender - cancelling order', [
                'final_exception' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'exception_class' => get_class($exception),
                'job_id' => $this->job ? $this->job?->getJobId() : 'unknown',
                'financing_order_id' => $traderOrder?->order?->id ?? null,
                'trader_order_id' => $this->traderOrderId,
                'final_attempt' => $finalAttempt,
                'timestamp' => saudi_now(),
            ]);

            if ($traderOrder) {
                app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
                app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
                (new RunHoldTraderWhenMarketOpenCommand)->handle();
            }

        } catch (Throwable $e) {
            Log::channel('bursam')->error('Failed to handle job failure', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'original_error' => $exception->getMessage(),
                'job_id' => $this->job ? $this->job?->getJobId() : 'unknown',
                'trader_order_id' => $this->traderOrderId,
                'timestamp' => saudi_now(),
            ]);
        }
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

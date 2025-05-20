<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Clients\LynkClient;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLynkTransferOwnershipToCustomer implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    public $tries = 3;

    public $maxExceptions = 3;

    public $timeout = 60;

    public $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $traderOrder = TraderOrder::where('status', TraderOrderStatus::InProgress)->find($this->traderOrderId);

            if (
                is_null($traderOrder)
                || ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::ContractSigned)
            ) {
                Log::channel('local_market')->info('Trader order not found to transfer ownership with reference: '.$this->traderOrderId);
                throw new \Exception('Trader order not found to transfer ownership with reference: '.$this->traderOrderId);
            }

            LynkClient::of($traderOrder)->transferOwnershipToCustomer();
        } catch (\Exception $e) {
            Log::error('ProcessLynkTransferOwnershipToCustomer failed', [
                'traderOrderId' => $this->traderOrderId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->fail($e);
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
        Log::error('ProcessLynkTransferOwnershipToCustomer', ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getMessage()]);
    }
}

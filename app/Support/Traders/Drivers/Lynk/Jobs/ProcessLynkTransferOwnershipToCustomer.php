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
        $traderOrder = TraderOrder::find($this->traderOrderId);

        if (is_null($traderOrder)) {
            Log::error('ProcessLynkTransferOwnershipToCustomer', [
                'trader_order_id' => $this->traderOrderId,
                'message' => 'Trader order not found to transfer ownership with reference: '.$this->traderOrderId,
            ]);
            throw new \Exception('Trader order not found to transfer ownership with reference: '.$this->traderOrderId);
        }

        if ($traderOrder->status !== TraderOrderStatus::InProgress) {
            Log::error('ProcessLynkTransferOwnershipToCustomer', [
                'trader_order_id' => $this->traderOrderId,
                'current_status' => $traderOrder->status,
                'message' => 'Trader order is not in progress with reference: '.$this->traderOrderId,
            ]);
            throw new \Exception('Trader order is not in progress with reference: '.$this->traderOrderId);
        }

        $isLastActionContractSigned = $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::ContractSigned);

        if (! $isLastActionContractSigned) {
            Log::error('ProcessLynkTransferOwnershipToCustomer', [
                'trader_order_id' => $this->traderOrderId,
                'last_action' => $isLastActionContractSigned,
                'message' => 'Trader order does not have contract signed action with reference: '.$this->traderOrderId.' and last action: '.$isLastActionContractSigned,
            ]);
            throw new \Exception('Trader order does not have contract signed action with reference: '.$this->traderOrderId.' and last action: '.$isLastActionContractSigned);
        }

        LynkClient::of($traderOrder)->transferOwnershipToCustomer();
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

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBursamTransferOwnershipToCustomer implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId) {
        $this->afterCommit = true;
        Log::channel('bursam')->info('ProcessBursamTransferOwnershipToCustomer: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if(is_null($traderOrder)){
                Log::channel('bursam')->warning('trader order not found traderOrderId: '.$this->traderOrderId.' in ProcessBursamTransferOwnershipToCustomer job', ['traderOrderId' => $this->traderOrderId]);
                return;
            }

            if($traderOrder->status->value !== TraderOrderStatus::InProgress){
                Log::channel('bursam')->warning('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in progress in ProcessBursamTransferOwnershipToCustomer job', ['traderOrderId' => $this->traderOrderId , 'status' => $traderOrder->status->value]);
                return;
            }


            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::ContractSigned)) {
                Log::channel('bursam')->warning('ProcessBursamTransferOwnershipToCustomer: traderOrderId: '.$this->traderOrderId.' - Job skipped - incorrect action state', [
                    'traderOrderId' => $this->traderOrderId ,
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'expected_action' => FinancingOrderHistory::ContractSigned,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                ]);
                return;
            }

            Trader::driver('bursam', $traderOrder->version)
                ->createSellingCommodityToCustomerDocument($traderOrder);
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

    public function failed($exception)
    {
        Log::error('ProcessBursamTransferOwnershipToCustomer', ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getMessage()]);
    }
}

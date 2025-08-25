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
        Log::channel(LOG_CHANNEL_BURSAM)->info('ProcessBursamTransferOwnershipToCustomer: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
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

            if (is_null($traderOrder)) {
                log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamTransferOwnershipToCustomer Job - not found trader_order_id => ' . $this->traderOrderId, [
                    'traderOrderId' => $this->traderOrderId,
                ]);
                return;
            }

            if($traderOrder->status->isNot(TraderOrderStatus::InProgress)){
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in progress in ProcessBursamTransferOwnershipToCustomer job', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId ,
                    'status' => $traderOrder->status->value]);
                return;
            }


            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::ContractSigned)) {
                log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at ProcessBursamTransferOwnershipToCustomer Job - incorrect action state', $traderOrder), [
                    'financingOrderId' => $traderOrder?->order?->id,
                    'traderOrderId' => $this->traderOrderId,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action  ,
                    'expected_action' => FinancingOrderHistory::ContractSigned,
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
        log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamTransferOwnershipToCustomer Job - trader_order_id => ' . $this->traderOrderId, [
            'traderOrderId ' => $this->traderOrderId, 
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

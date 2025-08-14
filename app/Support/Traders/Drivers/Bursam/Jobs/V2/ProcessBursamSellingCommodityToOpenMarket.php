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

class ProcessBursamSellingCommodityToOpenMarket implements ShouldBeUnique, ShouldQueue
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
        Log::channel('bursam')->info('ProcessBursamSellingCommodityToOpenMarket: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->where('status', TraderOrderStatus::InProgress)
                ->find($this->traderOrderId);

            if (! $traderOrder) {
                $fetchTraderOrder = TraderOrder::query()->find($this->traderOrderId);
                Log::channel('bursam')->warning('trader order not found traderOrderId: '.$this->traderOrderId.' with status in progress in ProcessBursamSellingCommodityToOpenMarket job', ['traderOrderId' => $this->traderOrderId , 'fetchTraderOrder' => $fetchTraderOrder]);
                return;
            }

            $trader = Trader::driver($traderOrder->provider, $traderOrder->version);

            if (! $trader->isOrderInSellableState($traderOrder)) {
                Log::channel('bursam')->warning('ProcessBursamSellingCommodityToOpenMarket: traderOrderId: '.$this->traderOrderId.' - Job skipped - incorrect action state', [
                    'traderOrderId' => $this->traderOrderId ,
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'expected_action' => FinancingOrderHistory::ClientWakalaAccepted,
                    'actual_last_action' => $traderOrder->traderHistories()->latest()->first()->action,
                ]);
                return;
            }

            $trader->sellCommodityToOpenMarket($traderOrder);
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
        Log::channel('bursam')->error('ProcessBursamSellingCommodityToOpenMarket', ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getMessage()]);
    }
}

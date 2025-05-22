<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Clients\LynkClient;
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

class ProcessLynkSellingCommodityToOpenMarket implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

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
     * @throws \Throwable
     */
    public function handle(): void
    {
        Log::info('ProcessLynkSellingCommodityToOpenMarket', ['traderOrderId' => $this->traderOrderId]);

        DB::transaction(function () {
            $traderOrder = TraderOrder::find($this->traderOrderId);

            if (! $traderOrder) {
                Log::error('ProcessLynkSellingCommodityToOpenMarket', [
                    'trader_order_id' => $this->traderOrderId,
                    'message' => 'Trader order not found with reference: '.$this->traderOrderId,
                ]);
                throw new \Exception('Trader order not found with reference: '.$this->traderOrderId);
            }

            if (! $traderOrder->status->is(TraderOrderStatus::InProgress)) {
                Log::error('ProcessLynkSellingCommodityToOpenMarket', [
                    'trader_order_id' => $this->traderOrderId,
                    'message' => 'Trader order is in progress with reference: '.$this->traderOrderId,
                ]);
                throw new \Exception('Trader order is in progress with reference: '.$this->traderOrderId);
            }

            $trader = Trader::driver($traderOrder->provider, $traderOrder->version);
            $isOrderInSellableState = $trader->isOrderInSellableState($traderOrder);
            if (! $isOrderInSellableState) {
                Log::error('ProcessLynkSellingCommodityToOpenMarket', [
                    'trader_order_id' => $this->traderOrderId,
                    'is_order_in_sellable_state' => $isOrderInSellableState,
                    'message' => 'Trader order is not in sellable state with reference: '.$this->traderOrderId,
                ]);
                throw new \Exception('Trader order is not in sellable state with reference: '.$this->traderOrderId);
            }

            LynkClient::of($traderOrder)->sellProduct();
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
        Log::error('ProcessLynkSellingCommodityToOpenMarket', ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getMessage()]);
    }
}

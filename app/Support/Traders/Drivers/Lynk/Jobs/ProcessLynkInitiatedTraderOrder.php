<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Enums\Trader as TraderEnum;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLynkInitiatedTraderOrder implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    public $tries = 5;

    public $backoff = [1, 2, 3, 5, 30];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('initiate_local_market_orders');
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        $traderOrder = TraderOrder::find($this->traderOrderId);

        if (is_null($traderOrder)) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('ProcessLynkInitiatedTraderOrder not found trader_order_id:' . $this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId,
            ]);
            throw new \Exception('Trader order not found to initiate with reference: '.$this->traderOrderId);
        }

        if (! $traderOrder->status->is(TraderOrderStatus::Initiated)) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLogTitle('ProcessLynkInitiatedTraderOrder trader order is not initiated status', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id, 
                'traderOrderId' => $traderOrder->id,
                'current_status' => $traderOrder->status,
                'expected_status' => TraderOrderStatus::Initiated,
            ]);
            throw new \Exception('Trader order is not initiated with reference: '.$this->traderOrderId);
        }

        Trader::driver(TraderEnum::Lynk, $traderOrder->version)->processInitiatedTraderOrder($traderOrder);
    }

    public function failed($exception)
    {
        $traderOrder = null;

        $traderOrder = TraderOrder::query()->find($this->traderOrderId);

        if (! $traderOrder) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('ProcessLynkInitiatedTraderOrder not found trader_order_id:' . $this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId,
            ]);
            throw new \Exception('Trader order not found to failed to initiate with reference: '.$this->traderOrderId);
        }

        app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
        log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLogTitle( method_exists('getMessage', $exception)? $exception->getMesage(): 'Cannot proceed to buy product', $traderOrder), 
            [
                'financingOrderId' => $traderOrder->financing_order_id, 
                'traderOrderId' => $this->traderOrderId,
                'exception' => $exception,
            ]
        );
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }
}

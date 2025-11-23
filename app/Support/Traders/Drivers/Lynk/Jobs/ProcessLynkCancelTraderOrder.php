<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ProcessLynkCancelTraderOrder implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TraderHelperTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('local_market_process');
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        $traderOrder = TraderOrder::query()
            ->find($this->traderOrderId);

        if (is_null($traderOrder)) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('ProcessLynkCancelTraderOrder not found trader_order_id:'.$this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId,
            ]);

            return;
        }

        if ($traderOrder->status->isNot(TraderOrderStatus::PendingCancellation)) {
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLogTitle(' trader order status is not cancelled at ProcessLynkCancelTraderOrder', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'status' => $traderOrder->status->value,
                'traderOrderId' => $this->traderOrderId,
            ]);

            return;
        }

        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, $traderOrder->cancelDetail->cancel_reason->value);
    }

    public function failed($exception)
    {
        $traderOrder = TraderOrder::query()->find($this->traderOrderId);

        if (! $traderOrder) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('ProcessLynkCancelTraderOrder not found at failed function trader_order_id => '.$this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId,
            ]);

            return;
        }

        $traderOrder->update([
            'status' => TraderOrderStatus::FailureToCancel,
        ]);

        log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLogTitle('failed at ProcessLynkCancelTraderOrder ', $traderOrder), [
            'financingOrderId' => $traderOrder->financing_order_id,
            'traderOrderId' => $this->traderOrderId,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
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

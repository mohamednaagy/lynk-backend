<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBursamInitiatedTraderOrder implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('bursam');
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        $traderOrder = TraderOrder::query()
            ->where('status', TraderOrderStatus::Initiated)
            ->find($this->traderOrderId);

        if (is_null($traderOrder)) {
            return;
        }

        Trader::driver('bursam', $traderOrder->version)->processInitiatedTraderOrder($traderOrder);
        Log::channel('bursam')->info('bursa purchasing step => finishing ProcessBursamInitiatedTraderOrder Job', ['traderOrderId' => $this->traderOrderId]);

    }

    public function failed($exception)
    {
        Log::channel('bursam')->error('ProcessBursamInitiatedTraderOrder failed method detail', [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'traderOrderId' => $this->traderOrderId,
        ]);

        $traderOrder = TraderOrder::query()->find($this->traderOrderId);

        if (! $traderOrder) {
            Log::channel('bursam')->error('trader order not found in ProcessBursamInitiatedTraderOrder failed method', ['traderOrderId' => $this->traderOrderId]);

            return;
        }

        app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);

    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
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

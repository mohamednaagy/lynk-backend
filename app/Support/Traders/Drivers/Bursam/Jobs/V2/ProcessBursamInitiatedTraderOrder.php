<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Exceptions\RateLimitExceededException;
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
use Illuminate\Support\Facades\DB;
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
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->where('status', TraderOrderStatus::Initiated)
                ->lockForUpdate()
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                return;
            }

            Trader::driver('bursam', $traderOrder->version)->processInitiatedTraderOrder($traderOrder);
        });
    }

    public function failed($exception)
    {
        $traderOrder = null;

        if ($exception instanceof RateLimitExceededException) {
            Log::warning('Rate limit exceeded for ProcessBursamInitiatedTraderOrder we will retry again soon', [
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        $traderOrder = TraderOrder::query()->find($this->traderOrderId);
        Log::channel('bursam')->error('ProcessBursamInitiatedTraderOrder exception detail', ['code' => $exception->getCode(),  'message' => $exception->getMessage()]);

        if (! $traderOrder) {
            return;
        }
        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);

        Log::error('ProcessBursamInitiatedTraderOrder', ['traderOrderId' => $this->traderOrderId,  'message' => $exception->getMessage()]);

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

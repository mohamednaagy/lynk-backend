<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
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

class ProcessLynkCancelTraderOrder implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $cancelledByType;

    public $cancelledBy;

    public function __construct(protected int $traderOrderId, protected int $cancelReason,
        $cancelledByType = TraderOrderCancelType::System,
        $cancelledBy = null)
    {
        $this->cancelledBy = $cancelledBy;
        $this->cancelledByType = $cancelledByType;

        $this->onQueue('local_market');
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
                ->where('status', TraderOrderStatus::PendingCancellation)
                ->lockForUpdate()
                ->find($this->traderOrderId);

            if (
                (is_null($traderOrder))) {
                return;
            }

            app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, $this->cancelReason, cancelledByType: $this->cancelledByType, cancelledBy: $this->cancelledBy);
        });
    }

    public function failed($exception)
    {
        $traderOrder = null;

        $traderOrder = TraderOrder::query()->find($this->traderOrderId);

        if (! $traderOrder) {
            return;
        }

        $traderOrder->update([
            'status' => TraderOrderStatus::FailureToCancel,
        ]);

        Log::error('ProcessLynkCancelTraderOrder', ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getMessage()]);
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

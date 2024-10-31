<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Models\User;
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
    protected int $cancelledByType;

    protected ?User $cancelledBy;

    protected int $cancelReason;

    public function __construct(protected int $traderOrderId, $cancelReason,
        $cancelledByType = TraderOrderCancelType::System,
        $cancelledBy = null)
    {
        $this->cancelledBy = $cancelledBy;
        $this->cancelledByType = $cancelledByType;
        $this->cancelReason = $cancelReason;

        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        try {
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
        } catch (\Exception $e) {
            Log::channel('local_market')->error('error at ProcessLynkCancelTraderOrder ,cant add cancel details ', ['trader_order_id' => $this->traderOrderId, 'error' => $e->getMessage()]);
        }

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

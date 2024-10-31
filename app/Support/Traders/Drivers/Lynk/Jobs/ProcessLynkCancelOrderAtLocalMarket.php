<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Clients\LynkClient;
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

class ProcessLynkCancelOrderAtLocalMarket implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected int $cancelReason;

    public function __construct(protected int $traderOrderId, $cancelReason)
    {
        $this->cancelReason = $cancelReason;
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            DB::transaction(function () {
                $traderOrder = TraderOrder::query()
                    ->where('status', TraderOrderStatus::PendingCancellation)
                    ->lockForUpdate()
                    ->find($this->traderOrderId);

                if (
                    (is_null($traderOrder))
                    || $this->cancelReason == TraderOrderCancelReason::FailureToPurchase
                    || $this->cancelReason == TraderOrderCancelReason::NoEligibleCommoditiesAvailable) {
                    return;
                }
                LynkClient::of($traderOrder)->cancelOrder();
            });
        } catch (\Exception $e) {
            Log::channel('local_market')->error('error at ProcessLynkCancelOrderAtLocalMarket , cant add connect to local market to cancel order ', ['trader_order_id' => $this->traderOrderId, 'error' => $e->getMessage()]);

        }

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

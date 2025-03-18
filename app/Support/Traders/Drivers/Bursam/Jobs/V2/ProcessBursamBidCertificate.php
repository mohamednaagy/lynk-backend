<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Console\Commands\RunHoldTraderWhenMarketOpenCommand;
use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBursamBidCertificate implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

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
     * @return void
     */
    public function handle()
    {
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->where('status', TraderOrderStatus::InProgress)
                ->lockForUpdate()
                ->find($this->traderOrderId);

            if (
                is_null($traderOrder)
                || ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiHoldingCertificateDocument)
            ) {
                return;
            }

            Log::channel('bursam')->info('bursa Purchasing Step => Starting ProcessBursamBidCertificate Job', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId]);

            Trader::driver('bursam', $traderOrder->version)
                ->getBidCertificateDetails($traderOrder);
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

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(30);
    }

    public function backoff(): array
    {
        return [60, 120, 180, 240, 300, 360, 420, 120];
    }

    public function failed($exception)
    {
        $traderOrder = TraderOrder::query()->find($this->traderOrderId);
        Log::channel('bursam')->error('bursa purchasing step => faild to get ProcessBursamBidCertificate and we will cancel order', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId, 'message' => $exception->getMessage()]);
        app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
        (new RunHoldTraderWhenMarketOpenCommand)->handle();

    }
}

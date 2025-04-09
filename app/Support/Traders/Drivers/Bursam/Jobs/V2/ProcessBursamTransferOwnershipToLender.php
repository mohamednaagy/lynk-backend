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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBursamTransferOwnershipToLender implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    public $tries = 10;

    public $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $traderOrder = TraderOrder::query()
            ->where('status', TraderOrderStatus::InProgress)
            ->lockForUpdate()
            ->find($this->traderOrderId);

        if (
            is_null($traderOrder)
            || ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachTtiHoldingCertificateDocument)
        ) {
            return;
        }
        Log::channel('bursam')->info('bursa Purchasing Step => Starting ProcessBursamTransferOwnershipToLender Job', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId]);

        Trader::driver('bursam', $traderOrder->version)
            ->createTransferOwnershipToLenderDocument($traderOrder);

        Log::channel('bursam')->info('bursa purchasing step => Finishing ProcessBursamTransferOwnershipToLender Job', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId]);

        (new RunHoldTraderWhenMarketOpenCommand)->handle();

        // fire the next step job
        ProcessBursamGenerateClientWakala::dispatch($this->traderOrderId);
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

        $traderOrder = TraderOrder::query()->find($this->traderOrderId);
        Log::channel('bursam')->error('bursa purchasing step => faild to get ProcessBursamTransferOwnershipToLender and we will cancel order', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId, 'message' => $exception->getMessage()]);
        app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);
        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, TraderOrderCancelReason::FailureToPurchase);

        (new RunHoldTraderWhenMarketOpenCommand)->handle();
    }
}

<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderCancelType;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBursamStbCertificateAfterCancellation implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    public $tries = 5;

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

        $this->onQueue('bursam');
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        Log::channel('bursam')->warning('start processing cancel trader order => bus chain (2) => ProcessBursamStbCertificateAfterCancellation', ['traderOrderId' => $this->traderOrderId, 'cancel_at' => now()->toDateTimeString()]);

        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->where('status', TraderOrderStatus::PendingCancellation)
                ->lockForUpdate()
                ->find($this->traderOrderId);

            if (
                (is_null($traderOrder)
                   || ! $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::GetSellingToMarketCertificate)) && ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::OnHold)
            ) {
                Log::channel('bursam')->warning('start processing cancel trader order => bus chain (2) => ProcessBursamStbCertificateAfterCancellation => start getStbCertificateDetails', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId, 'cancel_at' => now()->toDateTimeString()]);

                Trader::driver('bursam', $traderOrder->version)->getStbCertificateDetails($traderOrder);
            }
            Log::channel('bursam')->warning('start processing cancel trader order => bus chain (2) => ProcessBursamStbCertificateAfterCancellation => finish getStbCertificateDetails', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId, 'cancel_at' => now()->toDateTimeString()]);

            Log::channel('bursam')->warning('start processing cancel trader order => bus chain (2) => ProcessBursamStbCertificateAfterCancellation => start cancel step', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId, 'cancel_at' => now()->toDateTimeString()]);
            app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, $this->cancelReason);
            Log::channel('bursam')->warning('start processing cancel trader order => bus chain (2) => ProcessBursamStbCertificateAfterCancellation => finish cancel step', ['financingOrderId' => $traderOrder->order->id, 'traderOrderId' => $this->traderOrderId, 'cancel_at' => now()->toDateTimeString()]);

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

        Log::error('ProcessBursamStbCertificateAfterCancellation', ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getMessage()]);
    }

    public function backoff(): array
    {
        return [60, 120, 120];
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

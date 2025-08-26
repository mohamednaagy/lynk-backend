<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
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

    public $tries = 10;

    public $backoff = 30;

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
        Log::channel(LOG_CHANNEL_BURSAM)->info('ProcessBursamStbCertificateAfterCancellation: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        log::channel(LOG_CHANNEL_BURSAM)->info('start processing cancel trader_order_id => ' . $this->traderOrderId . ' at ProcessBursamStbCertificateAfterCancellation', ['traderOrderId' => $this->traderOrderId, 'cancel_at' => now()->toDateTimeString()]);

        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamStbCertificateAfterCancellation Job - not found trader order id:' . $this->traderOrderId, [
                    'traderOrderId' => $this->traderOrderId,
                ]);
                return;
            }

            if($traderOrder->status->isNot(TraderOrderStatus::PendingCancellation)){
                Log::channel(LOG_CHANNEL_BURSAM)->warning(formatLogTitle('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in pending cancellation in ProcessBursamStbCertificateAfterCancellation job', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId ,
                    'status' => $traderOrder->status->value
                ]);
                return;
            }

            Log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('start processing cancel trader order at ProcessBursamStbCertificateAfterCancellation', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrderId,
                'cancel_at' => now()->toDateTimeString()
            ]);
            if (
                ! $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::GetSellingToMarketCertificate)
                && $traderOrder->checkOrderStepComplete(MurabhaStep::PurchasingCommodity)
            ) {
                Trader::driver('bursam', $traderOrder->version)->getStbCertificateDetails($traderOrder);
            } else {
                log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at ProcessBursamStbCertificateAfterCancellation Job - incorrect action state', $traderOrder), [
                    'financingOrderId' => $traderOrder?->order?->id,
                    'traderOrderId' => $this->traderOrderId,
                    'is_trader_has_get_selling_to_market_certificate_action' => $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::GetSellingToMarketCertificate),
                    'complete_purchasing_step' => $traderOrder->checkOrderStepComplete(MurabhaStep::PurchasingCommodity),
                ]);
            }
        });
    }

    public function failed($exception)
    {
        $traderOrder = null;

        $traderOrder = TraderOrder::query()->find($this->traderOrderId);

        if (! $traderOrder) {
            log::channel(LOG_CHANNEL_BURSAM)->error('error at failed function at ProcessBursamStbCertificateAfterCancellation Job - not found trader_order_id =>' . $this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId,
            ]);
            return;
        }

        $traderOrder->update([
            'status' => TraderOrderStatus::FailureToCancel,
        ]);

        log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at  ProcessBursamStbCertificateAfterCancellation Job - failed to cancel trader order', $traderOrder), [
            'financingOrderId' => $traderOrder?->order->id,
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

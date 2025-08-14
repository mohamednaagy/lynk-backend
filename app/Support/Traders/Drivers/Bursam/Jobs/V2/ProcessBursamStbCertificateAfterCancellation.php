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
        Log::channel('bursam')->info('ProcessBursamStbCertificateAfterCancellation: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
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
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                Log::channel('bursam')->warning('trader order not found traderOrderId: '.$this->traderOrderId.' in ProcessBursamStbCertificateAfterCancellation job', ['traderOrderId' => $this->traderOrderId ]);

                return;
            }

            if($traderOrder->status->value !== TraderOrderStatus::PendingCancellation){
                Log::channel('bursam')->warning('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in pending cancellation in ProcessBursamStbCertificateAfterCancellation job', ['traderOrderId' => $this->traderOrderId , 'status' => $traderOrder->status->value]);
                return;
            }

            Log::channel('bursam')->info('start processing cancel trader order at ProcessBursamStbCertificateAfterCancellation', ['traderOrderId' => $this->traderOrderId, 'cancel_at' => now()->toDateTimeString()]);
            if (
                ! $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::GetSellingToMarketCertificate)
                && $traderOrder->checkOrderStepComplete(MurabhaStep::PurchasingCommodity)
            ) {
                Trader::driver('bursam', $traderOrder->version)->getStbCertificateDetails($traderOrder);
            }
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

        Log::channel('bursam')->error('ProcessBursamStbCertificateAfterCancellation', ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getTraceAsString()]);
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

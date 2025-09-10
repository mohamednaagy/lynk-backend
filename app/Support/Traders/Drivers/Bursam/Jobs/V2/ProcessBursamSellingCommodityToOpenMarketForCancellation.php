<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
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

class ProcessBursamSellingCommodityToOpenMarketForCancellation implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    public $tries = 10;

    public $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('bursam');
        Log::channel(LOG_CHANNEL_BURSAM)->info('ProcessBursamSellingCommodityToOpenMarketForCancellation: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        log::channel(LOG_CHANNEL_BURSAM)->info('start processing cancel trader order ProcessBursamSellingCommodityToOpenMarketForCancellation trader_order_id => '.$this->traderOrderId, ['traderOrderId' => $this->traderOrderId, 'cancel_at' => now()->toDateTimeString()]);

        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamSellingCommodityToOpenMarketForCancellation Job - not found trader_order_id => '.$this->traderOrderId, [
                    'traderOrderId' => $this->traderOrderId,
                ]);

                return;
            }

            if ($traderOrder->status->isNot(TraderOrderStatus::PendingCancellation)) {
                Log::channel(LOG_CHANNEL_BURSAM)->warning('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in pending cancellation in ProcessBursamSellingCommodityToOpenMarketForCancellation job', ['traderOrderId' => $this->traderOrderId, 'status' => $traderOrder->status->value]);

                return;
            }

            if ($traderOrder->checkOrderStepComplete(MurabhaStep::PurchasingCommodity)) {
                Trader::driver('bursam', $traderOrder->version)
                    ->sellCommodityToBursam($traderOrder);
                log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('start ProcessBursamSellingCommodityToOpenMarketForCancellation job - finish sellCommodityToBursam', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'cancel_at' => now()->toDateTimeString(),
                ]);
            } else {
                log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at ProcessBursamSellingCommodityToOpenMarketForCancellation Job - incorrect action state', $traderOrder), [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $this->traderOrderId,
                    'latest_action' => $traderOrder->traderHistories()->latest()->first()->action,
                    'complete_purchasing_step' => $traderOrder->checkOrderStepComplete(MurabhaStep::PurchasingCommodity),
                ]);
            }
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

    public function failed($exception)
    {
        log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamSellingCommodityToOpenMarketForCancellation Job - trader_order_id => '.$this->traderOrderId, ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
    }
}

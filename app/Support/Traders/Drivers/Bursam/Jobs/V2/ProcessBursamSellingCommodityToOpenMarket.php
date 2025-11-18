<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

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

class ProcessBursamSellingCommodityToOpenMarket implements ShouldBeUnique, ShouldQueue
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
        Log::channel(LOG_CHANNEL_BURSAM)->info('ProcessBursamSellingCommodityToOpenMarket: traderOrderId: '.$this->traderOrderId.' - Job constructor', ['traderOrderId' => $this->traderOrderId]);
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        Log::channel(LOG_CHANNEL_BURSAM)->info('ProcessBursamSellingCommodityToOpenMarket: traderOrderId: '.$this->traderOrderId.' - Job handle', ['traderOrderId' => $this->traderOrderId]);

        DB::beginTransaction();
        try {
            $traderOrder = TraderOrder::query()
                ->find($this->traderOrderId);

            if (! $traderOrder) {
                log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamSellingCommodityToOpenMarket Job - not found trader_order_id => '.$this->traderOrderId, [
                    'traderOrderId' => $this->traderOrderId,
                ]);

                return;
            }

            if ($traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
                Log::channel(LOG_CHANNEL_BURSAM)->warning('bursa purchasing step => trader order not found traderOrderId: '.$this->traderOrderId.' with status in progress in ProcessBursamSellingCommodityToOpenMarket job', ['traderOrderId' => $this->traderOrderId, 'status' => $traderOrder->status->value]);

                return;
            }

            $trader = Trader::driver($traderOrder->provider, $traderOrder->version);

            if (! $trader->isOrderInSellableState($traderOrder)) {
                log::channel(LOG_CHANNEL_BURSAM)->error(formatLogTitle('error at ProcessBursamSellingCommodityToOpenMarket Job - incorrect action state', $traderOrder), [
                    'financingOrderId' => $traderOrder?->order?->id,
                    'traderOrderId' => $this->traderOrderId,
                    'is_order_in_sellable_state' => $trader->isOrderInSellableState($traderOrder),
                ]);

                return;
            }

            $trader->sellCommodityToOpenMarket($traderOrder);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamSellingCommodityToOpenMarket Job - trader_order_id => '.$this->traderOrderId, [
                'traderOrderId' => $this->traderOrderId,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            throw $th;
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

    public function failed($exception)
    {
        log::channel(LOG_CHANNEL_BURSAM)->error('ProcessBursamSellingCommodityToOpenMarket Job Failed - trader_order_id => '.$this->traderOrderId, ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
    }
}

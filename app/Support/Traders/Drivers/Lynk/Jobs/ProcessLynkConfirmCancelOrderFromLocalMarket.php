<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

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

class ProcessLynkConfirmCancelOrderFromLocalMarket implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $traderOrder;

    public function __construct(protected string $traderOrderReference)
    {
        $this->onQueue('local_market_process');
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
                $this->traderOrder = TraderOrder::query()
                    ->where('reference', $this->traderOrderReference)
                    ->lockForUpdate()
                    ->first();

                if (is_null($this->traderOrder)) {
                    log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('ProcessLynkConfirmCancelOrderFromLocalMarket not found trader_order_reference:'.$this->traderOrderReference, [
                        'traderOrderReference' => $this->traderOrderReference,
                    ]);

                    return;
                }
                Trader::driver($this->traderOrder->provider, $this->traderOrder->version)->confirmCancelledFromProvider($this->traderOrder);
            });
        } catch (\Exception $e) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLogTitle('error ProcessLynkConfirmCancelOrderFromLocalMarket', $this->traderOrder), [
                'financingOrderId' => $this->traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrder->id,
                'message' => $e->getMessage(),
            ]);

        }

    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderReference;
    }
}

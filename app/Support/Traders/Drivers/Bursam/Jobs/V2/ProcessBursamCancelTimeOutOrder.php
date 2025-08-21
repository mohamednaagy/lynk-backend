<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\TraderOrderCancelReason;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBursamCancelTimeOutOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected FinancingOrder $financingOrder)
    {
        $this->onQueue('bursam');
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        log::channel(LOG_CHANNEL_BURSAM)->info('start ProcessBursamCancelTimeOutOrder Job - financing_order_id => ' . $this->financingOrder->id, ['financingOrderId' => $this->financingOrder->id]);

        DB::transaction(function () {
            $lockedFinancingOrder = FinancingOrder::query()
                ->find($this->financingOrder->id);

            if (is_null($lockedFinancingOrder)) {
                log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamCancelTimeOutOrder Job - not found financing_order_id => ' . $this->financingOrder->id, ['financingOrderId' => $this->financingOrder->id]);
                return;
            }

            $lockedFinancingOrder->activeTraderOrder->each(function ($activeTraderOrder) {
                Trader::driver($activeTraderOrder->provider, $activeTraderOrder->version)
                    ->cancelTraderOrder($activeTraderOrder, TraderOrderCancelReason::MurabhaTimeout);
            });
        });
    }

    public function failed($exception)
    {
        log::channel(LOG_CHANNEL_BURSAM)->error('error at ProcessBursamCancelTimeOutOrder Job - financing_order_id => ' . $this->financingOrder->id, ['financingOrderId' => $this->financingOrder->id,  'message' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
    }
}

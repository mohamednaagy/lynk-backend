<?php

namespace App\Jobs\TraderOrder;

use App\Models\TraderOrder;
use App\Enums\TraderOrderCancelReason;
use App\Models\TraderOrderTimeLimit;
use App\Services\TraderOrder\TimeLimitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Support\Traders\Facades\Traders;

class ExpireOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var TraderOrderTimeLimit
     */
    private TraderOrderTimeLimit $traderOrderTimeLimit;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(TraderOrderTimeLimit $traderOrderTimeLimit)
    {
        $this->traderOrderTimeLimit = $traderOrderTimeLimit;
        $this->onQueue('expire_trader_order');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $traderOrder = TraderOrder::find($this->traderOrderTimeLimit->trader_order_id);
            if ($traderOrder->isExpirable()) {
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::ExpiredConfirmationTimeLimit);
                $this->traderOrderTimeLimit->expire();
            }
        } catch(\Exception $exception) {
            $this->traderOrderTimeLimit->fail();
        }
    }
}

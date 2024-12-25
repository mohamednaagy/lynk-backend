<?php

namespace App\Jobs\TraderOrder;

use App\Enums\TraderOrderCancelReason;
use App\Models\TraderOrder;
use App\Models\TraderOrderTimeLimit;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private TraderOrderTimeLimit $traderOrderTimeLimit;

    private string $jobUniqueId;

    public function __construct(int $traderOrderTimeLimitId)
    {
        $this->traderOrderTimeLimit = TraderOrderTimeLimit::findOrFail($traderOrderTimeLimitId);
        $this->jobUniqueId = 'expire_trader_order_'.$this->traderOrderTimeLimit->trader_order_id;
        $this->onQueue('expire_trader_order');
    }

    public function handle()
    {
        try {
            $traderOrder = TraderOrder::find($this->traderOrderTimeLimit->trader_order_id);

            if (! $traderOrder) {
                $this->traderOrderTimeLimit->fail();

                return;
            }

            if ($traderOrder->isExpirable()) {
                Trader::driver($traderOrder->provider, $traderOrder->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::ExpiredConfirmationTimeLimit);

                $this->traderOrderTimeLimit->expire();
                Log::info("Expire order {$traderOrder->id} successfully");
            } else {
                Log::info("Order {$traderOrder->id} is not expirable", [
                    'time_limit' => $this->traderOrderTimeLimit,
                ]);
            }
        } catch (\Exception $exception) {
            Log::error("ExpireOrderJob failed: {$exception->getMessage()}", [
                'time_limit_id' => $this->traderOrderTimeLimit->id,
                'exception' => $exception,
            ]);
            $this->traderOrderTimeLimit->fail();
        }
    }

    public function getJobUniqueId(): string
    {
        return $this->jobUniqueId;
    }
}

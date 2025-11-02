<?php

namespace App\Jobs\TraderOrder;

use App\Actions\Contracts\Orders\TraderOrders\CheckTraderOrderSettlement as CheckTraderOrderSettlementInterface;
use App\Models\TraderOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CheckTraderOrderSettlementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        private int $traderOrderId
    ) {
        $this->onQueue('local_market_commodities_settlement');
        Log::info('CheckTraderOrderSettlementJob job queued trader order id => '.$this->traderOrderId, [
            'traderOrderId' => $this->traderOrderId,
        ]);
    }

    public function handle(CheckTraderOrderSettlementInterface $checkSettlement): void
    {
        $traderOrder = TraderOrder::query()->findOrFail($this->traderOrderId);

        $checkSettlement->handle($traderOrder);
    }
}

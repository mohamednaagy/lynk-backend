<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrderSettlement;
use App\Enums\TraderOrderSettlementStatus;
use App\Jobs\TraderOrder\CheckTraderOrderSettlementJob;
use App\Models\TraderOrder;
use App\Models\TraderOrderSettlement;

class InitiateTraderOrderSettlementAction implements InitiateTraderOrderSettlement
{
    public function handle(TraderOrder $traderOrder): ?TraderOrderSettlement
    {
        if ($traderOrder->isCommoditiesSettled()) {
            return $traderOrder->latestSettlement;
        }

        if ($traderOrder->hasPendingSettlementCheck()) {
            return null;
        }

        $settlement = TraderOrderSettlement::create([
            'trader_order_id' => $traderOrder->id,
            'status' => TraderOrderSettlementStatus::Pending,
            'creator_id' => auth()->id(),
        ]);

        CheckTraderOrderSettlementJob::dispatch($traderOrder->id);

        return $settlement;
    }
}

<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Actions\Contracts\Orders\TraderOrders\AutoCompleteSell;
use App\Enums\FinancingOrderProceedCase;
use App\Models\TraderOrder;

class AutoCompleteSellAction implements AutoCompleteSell
{
    public function handle(int $traderOrderId, int $periodId): void
    {
        $traderOrder = TraderOrder::findOrFail($traderOrderId);

        app(MakeOrderProceed::class)->handle(
            $traderOrder,
            FinancingOrderProceedCase::ContractAndClientWakalaCompleted,
            true
        );

        $traderOrder->setAutoCompletePeriodId($periodId);
    }
}

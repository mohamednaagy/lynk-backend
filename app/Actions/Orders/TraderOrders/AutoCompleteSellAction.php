<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\MakeOrderProceed;
use App\Actions\Contracts\Orders\TraderOrders\AutoCompleteSell;
use App\Enums\FinancingOrderProceedCase;
use App\Models\ClientAutoSellPeriod;
use App\Models\TraderOrder;

class AutoCompleteSellAction implements AutoCompleteSell
{
    public function handle(TraderOrder $traderOrder, ClientAutoSellPeriod $period): void
    {
        app(MakeOrderProceed::class)->handle(
            $traderOrder,
            FinancingOrderProceedCase::ContractAndClientWakalaCompleted,
            true
        );

        $traderOrder->setAutoCompletePeriodId($period->id);
    }
}

<?php

namespace App\Actions\Orders\TraderOrders\Fees;

use App\Actions\Contracts\Orders\TraderOrders\Fees\DeductBalanceForCompletedOrder;
use App\Actions\Contracts\Wallets\DeductOrderCompletedFee;
use App\Models\TraderOrder;

class DeductBalanceForCompletedOrderAction implements DeductBalanceForCompletedOrder
{
    public function __construct(
        protected DeductOrderCompletedFee $deductOrderCompletedFee
    ) {
    }

    public function handle(TraderOrder $traderOrder): void
    {
        $this->deductOrderCompletedFee->handle($traderOrder);
    }
}

<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\DeductBalanceForCompletedOrder;
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

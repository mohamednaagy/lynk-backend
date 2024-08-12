<?php

namespace App\Actions\Orders\TraderOrders\Fees;

use App\Actions\Contracts\Orders\TraderOrders\Fees\DeductBalanceForNewOrder;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Models\TraderOrder;

class DeductBalanceForNewOrderAction implements DeductBalanceForNewOrder
{
    public function __construct(
        protected DeductOrderCreationFee $deductOrderCreationFee
    ) {
    }

    public function handle(TraderOrder $traderOrder): void
    {
        $this->deductOrderCreationFee->handle($traderOrder);
    }
}

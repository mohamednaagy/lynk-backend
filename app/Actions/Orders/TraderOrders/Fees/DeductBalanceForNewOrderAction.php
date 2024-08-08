<?php

namespace App\Actions\Orders\TraderOrders\Fees;

use App\Actions\Contracts\Orders\TraderOrders\Fees\DeductBalanceForNewOrder;
use App\Actions\Wallets\TraderOrders\Fees\DeductOrderCreationFeeAction;
use App\Models\TraderOrder;

class DeductBalanceForNewOrderAction implements DeductBalanceForNewOrder
{
    public function __construct(
        protected DeductOrderCreationFeeAction $deductOrderCreationFee
    ) {}

    public function handle(TraderOrder $traderOrder): void
    {
        $this->deductOrderCreationFee->handle($traderOrder);
    }
}

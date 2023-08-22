<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\DeductBalanceForNewOrder;
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
        // deduct the cost from the wallet
        $creationFeeTransaction = $this->deductOrderCreationFee->handle($traderOrder);
    }
}

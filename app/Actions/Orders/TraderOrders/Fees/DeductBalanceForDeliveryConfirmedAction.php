<?php

namespace App\Actions\Orders\TraderOrders\Fees;

use App\Actions\Contracts\Orders\TraderOrders\Fees\DeductBalanceForDeliveryConfirmed;
use App\Actions\Contracts\Wallets\DeductOrderDeliveryConfirmedFee;
use App\Models\TraderOrder;

class DeductBalanceForDeliveryConfirmedAction implements DeductBalanceForDeliveryConfirmed
{
    public function __construct(
        protected DeductOrderDeliveryConfirmedFee $deductOrderDeliveryConfirmedFee
    ) {
    }

    public function handle(TraderOrder $traderOrder): void
    {
        $this->deductOrderDeliveryConfirmedFee->handle($traderOrder);
    }
}

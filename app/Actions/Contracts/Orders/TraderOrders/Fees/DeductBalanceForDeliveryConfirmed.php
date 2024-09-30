<?php

namespace App\Actions\Contracts\Orders\TraderOrders\Fees;

use App\Models\TraderOrder;

interface DeductBalanceForDeliveryConfirmed
{
    public function handle(TraderOrder $traderOrder): void;
}

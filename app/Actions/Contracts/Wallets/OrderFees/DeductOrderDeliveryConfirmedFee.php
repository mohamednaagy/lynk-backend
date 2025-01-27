<?php

namespace App\Actions\Contracts\Wallets\OrderFees;

use App\Models\TraderOrder;

interface DeductOrderDeliveryConfirmedFee
{
    public function handle(TraderOrder $traderOrder);
}

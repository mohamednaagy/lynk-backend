<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\TraderOrder;

interface DeductOrderDeliveryConfirmedFee
{
    public function handle(TraderOrder $traderOrder);
}

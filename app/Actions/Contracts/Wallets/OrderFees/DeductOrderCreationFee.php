<?php

namespace App\Actions\Contracts\Wallets\OrderFees;

use App\Models\TraderOrder;

interface DeductOrderCreationFee
{
    public function handle(TraderOrder $traderOrder);
}

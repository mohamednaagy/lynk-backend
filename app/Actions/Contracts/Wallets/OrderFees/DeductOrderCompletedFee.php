<?php

namespace App\Actions\Contracts\Wallets\OrderFees;

use App\Models\TraderOrder;

interface DeductOrderCompletedFee
{
    public function handle(TraderOrder $traderOrder);
}

<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\TraderOrder;

interface DeductOrderCompletedFee
{
    public function handle(TraderOrder $traderOrder);
}

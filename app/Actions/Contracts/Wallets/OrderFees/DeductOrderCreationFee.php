<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\TraderOrder;

interface DeductOrderCreationFee
{
    public function handle(TraderOrder $traderOrder);
}

<?php

namespace App\Actions\Contracts\Wallets;

use App\Models\FinancingOrder;

interface DeductVatPercentage
{
    public function handle(FinancingOrder $financingOrder);
}

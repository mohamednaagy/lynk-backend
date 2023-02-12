<?php

namespace App\Actions\Contracts\Orders;

use App\Models\TraderOrder;

interface MakeOrderProceed
{
    public function handle(TraderOrder $traderOrder, string $case, bool $forceToProceed);
}

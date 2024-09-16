<?php

namespace App\Actions\Contracts\Orders\TraderOrders\ProceedAction;

use App\Models\TraderOrder;

interface ProceedContractSigned
{
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): array;
}

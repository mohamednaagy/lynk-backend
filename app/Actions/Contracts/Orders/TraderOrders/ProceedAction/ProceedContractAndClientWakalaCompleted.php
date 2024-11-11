<?php

namespace App\Actions\Contracts\Orders\TraderOrders\ProceedAction;

use App\Models\TraderOrder;

interface ProceedContractAndClientWakalaCompleted
{
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): array;
}

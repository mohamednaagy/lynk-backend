<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;

interface FireWebhookWhenStatusIsMurabhaSaleCompleted
{
    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void;
}

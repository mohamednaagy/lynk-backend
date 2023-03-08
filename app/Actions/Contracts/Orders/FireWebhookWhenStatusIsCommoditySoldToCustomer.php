<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;

interface FireWebhookWhenStatusIsCommoditySoldToCustomer
{
    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void;
}

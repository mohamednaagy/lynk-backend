<?php

namespace App\Actions\Contracts\Orders\Webhooks;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;

interface FireWebhookWhenStatusIsCommodityPurchased
{
    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void;
}

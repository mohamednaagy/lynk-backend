<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;

interface FireWebhookWhenStatusIsCommodityPurchased
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void;
}

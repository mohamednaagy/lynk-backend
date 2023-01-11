<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;

interface CommodityPurchasedOrderStatus
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void;
}

<?php

namespace App\Support\Traders\Drivers\Dmcc\Strategies;

use App\Models\FinancingOrder;

class DmccV2Driver extends DmccV1Driver
{
    public function createTraderOrder(FinancingOrder $financingOrder): string
    {
        // TODO: Implement createTraderOrder() method.
    }

    public function fetchOrderResult(string $type): ?array
    {
        // TODO: Implement fetchNotifications() method.
    }

    public function cancelOrder(FinancingOrder $financingOrder): object
    {
        // TODO: Implement cancelOrder() method.
    }

    public function SellingCommodity(string $type)
    {
        // TODO: Implement SellingCommodity() method.
    }
}

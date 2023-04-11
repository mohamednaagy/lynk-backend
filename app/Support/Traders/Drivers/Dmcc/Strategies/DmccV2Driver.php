<?php

namespace App\Support\Traders\Drivers\Dmcc\Strategies;

use App\Models\FinancingOrder;
use App\Support\Traders\Contracts\TraderInterface;

class DmccV2Driver implements TraderInterface
{
    public function createTraderOrder(FinancingOrder $financingOrder)
    {
        // TODO: Implement createTraderOrder() method.
    }

    public function acceptAgreement()
    {
        // TODO: Implement acceptAgreement() method.
    }

    public function fetchNotifications(string $type)
    {
        // TODO: Implement fetchNotifications() method.
    }

    public function cancelOrder(FinancingOrder $financingOrder): mixed
    {
        // TODO: Implement cancelOrder() method.
    }
}

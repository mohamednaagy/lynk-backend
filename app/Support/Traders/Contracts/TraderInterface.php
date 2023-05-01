<?php

namespace App\Support\Traders\Contracts;

use App\Models\FinancingOrder;

interface TraderInterface
{
    public function createTraderOrder(FinancingOrder $financingOrder);

    public function transferOwnershipToCustomer(FinancingOrder $financingOrder);

    public function sellingCommodityToOpenMarket(FinancingOrder $financingOrder);

    public function cancelOrder(FinancingOrder $financingOrder): mixed;
}

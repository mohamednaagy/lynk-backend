<?php

namespace App\Support\Traders\Contracts;

use App\Models\FinancingOrder;

interface TraderInterface
{
    public function createTraderOrder(FinancingOrder $financingOrder);

    public function ownershipToCustomer(FinancingOrder $financingOrder);

    public function sellingCommodity(FinancingOrder $financingOrder);

    public function cancelOrder(FinancingOrder $financingOrder): mixed;
}

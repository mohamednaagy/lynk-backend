<?php

namespace App\Support\Traders\Contracts;

use App\Models\FinancingOrder;

interface TraderInterface
{
    public function createTraderOrder(FinancingOrder $financingOrder);

    public function fetchOrderResult(string $type);

    // we need to decide the following commented methods should be in interface or not
//    public function ownershipToCustomer(FinancingOrder $financingOrder);
//
//    public function sellingCommodity(FinancingOrder $financingOrder);

    public function cancelOrder(FinancingOrder $financingOrder): mixed;
}

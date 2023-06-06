<?php

namespace App\Support\Traders\Contracts;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;

interface TraderInterface
{
    public function createTraderOrder(FinancingOrder $financingOrder);

    public function createSellingCommodityToCustomerDocument(TraderOrder $traderOrder);

    public function sellingCommodityToOpenMarket(TraderOrder $traderOrder);

    public function cancelOrder(FinancingOrder $financingOrder): mixed;

    public function isTraderOrderCancellable(TraderOrder $traderOrder);
}

<?php

namespace App\Support\Traders\Contracts;

use App\Enums\TraderOrderCancelReason;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;

interface TraderInterface
{
    public function createTraderOrder(FinancingOrder $financingOrder): TraderOrder;

    public function createSellingCommodityToCustomerDocument(TraderOrder $traderOrder);

    public function sellCommodityToOpenMarket(TraderOrder $traderOrder);

    public function cancelOrder(FinancingOrder $financingOrder): mixed;

    public function isTraderOrderCancellable(TraderOrder $traderOrder, ?string $area);

    public function cancelTraderOrder(
        TraderOrder $traderOrder,
        int $cancelReason = TraderOrderCancelReason::Manual
    ): mixed;

    public function getDefaultInitialTradeOrderStatus();

    public function processProceedContractAndClientWakala(TraderOrder $traderOrder);

    public function contractSignedMessage(TraderOrder $traderOrder);
}

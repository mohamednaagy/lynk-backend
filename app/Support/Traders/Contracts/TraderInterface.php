<?php

namespace App\Support\Traders\Contracts;

use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;

interface TraderInterface
{
    public function createTraderOrder(FinancingOrder $financingOrder): TraderOrder;

    public function createSellingCommodityToCustomerDocument(TraderOrder $traderOrder);

    public function sellCommodityToOpenMarket(TraderOrder $traderOrder);

    public function cancelOrder(FinancingOrder $financingOrder): mixed;

    public function isTraderOrderCancellable(TraderOrder $traderOrder, ?string $area);

    public function cancelTraderOrder(
        TraderOrder $traderOrder,
        int $cancelReason = TraderOrderCancelReason::Manual,
        $cancelledByType = TraderOrderCancelType::System,
        ?User $cancelledBy = null
    ): mixed;

    public function getDefaultInitialTradeOrderStatus();

    public function processProceedContractAndClientWakala(TraderOrder $traderOrder);

    public function checkCanInitiateTraderOrder();

    public function moveHoldTraderOrder(TraderOrder $traderOrder);

    public function HoverMessageOfTraderStatus(TraderOrder $traderOrder): ?string;

    public function contractSignedMessage(TraderOrder $traderOrder);

    public function retryOrder(TraderOrder $traderOrder);

    public function confirmCancelledFromProvider(TraderOrder $traderOrder): void;
}

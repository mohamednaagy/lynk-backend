<?php

namespace App\Support\Traders\Contracts;

use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;

interface TraderInterface
{
    public function createTraderOrder(FinancingOrder $financingOrder, ?int $commodityTypeId = null): TraderOrder;

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

    public function processProceedContractSigned(TraderOrder $traderOrder): void;

    public function processProceedContractAndClientWakala(TraderOrder $traderOrder);

    public function checkCanInitiateTraderOrder();

    public function moveHoldTraderOrder(TraderOrder $traderOrder);

    public function hoverMessageOfTraderStatus(TraderOrder $traderOrder): ?string;

    public function contractSignedMessage(TraderOrder $traderOrder): ?string;

    public function clientWakalaMessage(TraderOrder $traderOrder): ?string;

    public function confirmCancelledFromProvider(TraderOrder $traderOrder): void;

    public function handleConfirmDelivery(TraderOrder $traderOrder);

    public function handleRequestDeliverCommodityToCustomer(TraderOrder $traderOrder);

    public function isOrderInSellableState(TraderOrder $traderOrder): bool;

    public function isContractSignLimitEligibleForExpiry(TraderOrder $traderOrder): bool;
}

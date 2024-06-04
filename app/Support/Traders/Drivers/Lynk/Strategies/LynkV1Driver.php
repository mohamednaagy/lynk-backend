<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Enums\OrderCancellationStatus;
use App\Enums\TraderOrderCancellationStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Contracts\TraderInterface;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;

// TODO_LOCAL_MARKET need to review
class LynkV1Driver implements TraderInterface
{
    use Localizable;
    use TraderHelperTrait {
        createTraderOrder as traitCreateTraderOrder;
    }

    protected $provider = 'lynk';

    protected $version = 'v1';

    public function getOrInitiateTraderOrder(FinancingOrder $financingOrder): ?Model
    {
        if ($financingOrder->initiatedTraderOrders()->exists()) {
            return $financingOrder->initiatedTraderOrders()->first();
        }

        return $financingOrder->traderOrders()->create([
            'uuid_one' => Str::uuid(),
            'provider' => $this->provider,
            'reference' => '',
            'status' => TraderOrderStatus::Initiated,
            'version' => $this->version,
            'mode' => TraderOrderMode::Automatic,
        ]);
    }

    /**
     * @throws TraderException
     */
    public function createTraderOrder(FinancingOrder $financingOrder): TraderOrder
    {
        return $this->getOrInitiateTraderOrder($financingOrder);
    }

    public function createSellingCommodityToCustomerDocument(TraderOrder $traderOrder)
    {        // TODO_LOCAL_MARKET need to implement

    }

    public function sellCommodityToOpenMarket(TraderOrder $traderOrder)
    {
        // TODO_LOCAL_MARKET need to implement
    }

    public function cancelOrder(FinancingOrder $financingOrder): int
    {
        // TODO_LOCAL_MARKET need to implement
        return OrderCancellationStatus::PendingCancellation;
    }

    public function isTraderOrderCancellable(TraderOrder $traderOrder, ?string $area)
    {
        // TODO_LOCAL_MARKET need to implement

        return true;
    }

    public function cancelTraderOrder(
        TraderOrder $traderOrder,
        int $cancelReason = TraderOrderCancelReason::Manual
    ): int {
        // TODO_LOCAL_MARKET need to implement
        return TraderOrderCancellationStatus::Cancelled;
    }

    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder): void
    {
    }
}

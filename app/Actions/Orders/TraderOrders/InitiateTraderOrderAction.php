<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrder;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\TraderOrderMode;
use App\Exceptions\OrderAlreadyHasActiveTraderOrderException;
use App\Exceptions\OrderHasCompletedTraderOrderException;
use App\Exceptions\TradeRequestCreationNotAllowedException;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Support\Traders\Facades\Trader;

class InitiateTraderOrderAction implements InitiateTraderOrder
{
    /**
     * @throws OrderHasCompletedTraderOrderException
     * @throws TradeRequestCreationNotAllowedException
     * @throws OrderAlreadyHasActiveTraderOrderException
     */
    public function handle(User $user, int $orderId): void
    {
        $order = FinancingOrder::findOrFail($orderId);

        $this->ensureTraderOrderCanBeCreated($order, $user);

        $driver = $order->getPreferredTrader();
        $trader = Trader::driver($driver, get_latest_version_of_trader($driver));
        $trader->createTraderOrder($order);

        // Ensure status is updated only after successful trader order creation
        $order->update([
            'status' => FinancingOrderStatus::InProgress,
        ]);
    }

    private function ensureTraderOrderCanBeCreated(FinancingOrder $order, User $user): void
    {
        if (! $order->canCreateTraderOrder($user)) {
            if ($order->hasCompletedTraderOrder()) {
                throw new OrderHasCompletedTraderOrderException($order->id);
            }

            if ($order->isTradingMode(TraderOrderMode::Manual) && $user->hasRole(Role::LenderApiUser)) {
                throw new TradeRequestCreationNotAllowedException;
            }

            throw new OrderAlreadyHasActiveTraderOrderException;
        }
    }
}

<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Enums\WalletType;
use App\Exceptions\BalanceIsNotEnoughException;
use App\Exceptions\NoMatchOrderCostAndValueException;
use App\Models\Lender;
use App\Models\TieredPricing;
use Cknow\Money\Money;

class CanCreateOrderAction implements CanCreateOrder
{
    /**
     * @throws NoMatchOrderCostAndValueException
     * @throws BalanceIsNotEnoughException
     */
    public function handle(Lender $lender, Money $amount): bool
    {
        $wallet = $lender->getWallet(WalletType::CompanyWallet);
        $orderCostWithVat = TieredPricing::getOrderCostWithVat($lender, $amount);

        if ($wallet->balance->greaterThanOrEqual($orderCostWithVat)) {
            return true;
        }

        throw new BalanceIsNotEnoughException;
    }
}

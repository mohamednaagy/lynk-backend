<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Enums\WalletType;
use App\Exceptions\BalanceIsNotEnoughException;
use App\Exceptions\NoMatchOrderCostAndValueException;
use App\Models\Company;
use App\Models\TieredPricing;
use Cknow\Money\Money;

class CanCreateOrderAction implements CanCreateOrder
{
    /**
     * @throws NoMatchOrderCostAndValueException
     * @throws BalanceIsNotEnoughException
     */
    public function handle(Company $company, Money $amount): bool
    {
        $wallet = $company->getWallet(WalletType::CompanyWallet);
        $orderCost = TieredPricing::getOrderCost($company, $amount);
        [$vatOfOrderCost] = app(CalculateVatAmount::class)
            ->setAmount($orderCost)
            ->setIsVatIncludedInAmount(false)
            ->handle();

        if ($wallet->balance->greaterThanOrEqual($orderCost->add($vatOfOrderCost))) {
            return true;
        }

        throw new BalanceIsNotEnoughException();
    }
}

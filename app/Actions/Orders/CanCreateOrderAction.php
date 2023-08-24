<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Enums\WalletType;
use App\Exceptions\BalanceIsNotEnoughException;
use App\Models\Company;

class CanCreateOrderAction implements CanCreateOrder
{
    public function __construct(protected CalculateVatAmount $calculateVatAmount)
    {
    }

    public function handle(Company $company): bool
    {
        $wallet = $company->getWallet(WalletType::CompanyWallet);
        [$vatAmount] = $this->calculateVatAmount
            ->setAmount($company->order_cost)
            ->setIsVatIncludedInAmount(false)
            ->handle();

        if ($wallet->balance->greaterThanOrEqual(
            $company->order_cost->add($vatAmount)
        )) {
            return true;
        }

        throw new BalanceIsNotEnoughException();
    }
}

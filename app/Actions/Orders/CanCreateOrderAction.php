<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Enums\WalletType;
use App\Exceptions\BalanceIsNotEnoughException;
use App\Models\Company;

class CanCreateOrderAction implements CanCreateOrder
{
    public function handle(Company $company): bool
    {
        $wallet = $company->getWallet(WalletType::CompanyWallet);

        if ($wallet->balance >= $company->order_cost) {
            return true;
        }

        throw new BalanceIsNotEnoughException();
    }
}

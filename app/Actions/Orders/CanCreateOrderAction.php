<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Enums\WalletType;
use App\Models\Company;

class CanCreateOrderAction implements CanCreateOrder
{
    public function handle(Company $company): bool
    {
        $wallet = $company->getWallet(WalletType::CompanyWallet);

        return $wallet->balance > tenant()->order_cost;
    }
}

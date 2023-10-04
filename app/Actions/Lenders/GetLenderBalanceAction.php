<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Enums\WalletType;
use App\Models\Company;
use App\Models\TieredPricing;
use Money\Money;

class GetLenderBalanceAction implements GetLenderBalance
{
    public function __construct()
    {
    }

    public function handle(Company $company): array
    {
        $balance = $company->balance(WalletType::CompanyWallet);
        $orderCost = TieredPricing::getOrderCostIfStandard($company);

        return [
            'balance' => $balance,
            'availableOrders' => $orderCost
                ? $balance->divide($orderCost['costWithVat']->getAmount(), Money::ROUND_DOWN)->getAmount()
                : null,
        ];
    }
}

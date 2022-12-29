<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Enums\WalletType;
use App\Models\Company;
use Money\Money;

class GetLenderBalanceAction implements GetLenderBalance
{
    /**
     * Update user.
     *
     * @param  Company  $company
     * @return array $user
     */
    public function handle(Company $company): array
    {
        $balance = $company->balance(WalletType::CompanyWallet);
        $orderCost = $company->order_cost->getAmount();

        return [
            'balance' => $balance,
            'availableOrders' => $orderCost ? $balance->divide($orderCost, Money::ROUND_DOWN)->getAmount() : 0,
        ];
    }
}

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

        return [
            'balance' => $balance,
            'availableOrders' => $balance->divide($company->order_cost->getAmount(), Money::ROUND_DOWN)
                ->getAmount(),
        ];
    }
}

<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Enums\WalletType;
use App\Models\Company;

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
        $wallet = $company->getWallet(WalletType::CompanyWallet);

        return [
            'balance' => $wallet->balanceFloat,
            'availableOrders' => floor($wallet->balanceFloat / $company->order_cost),
        ];
    }
}

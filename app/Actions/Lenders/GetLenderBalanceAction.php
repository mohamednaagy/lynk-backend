<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetLenderBalance;
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
        return [
            'balance' => $company->balance,
            'availableOrders' => floor($company->balance / $company->order_cost),
        ];
    }
}

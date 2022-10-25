<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Models\Company;
use App\Models\User;

class GetLenderBalanceAction implements GetLenderBalance
{
    /**
     * Update user.
     *
     * @param  array  $data
     * @param  User  $user
     * @return void $user
     */
    public function handle(Company $company): array
    {
        return [
            'balance' => $company->balanceFloat,
            'available_orders' => floor($company->balanceFloat / $company->order_cost),
        ];
    }
}

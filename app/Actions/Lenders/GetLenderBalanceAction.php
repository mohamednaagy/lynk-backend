<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Enums\WalletType;
use App\Models\Company;
use Money\Money;

class GetLenderBalanceAction implements GetLenderBalance
{
    public function __construct(
        protected GetProjectSettings $getProjectSettings
    ) {
    }

    /**
     * Update user.
     *
     * @param  Company  $company
     * @return array $user
     */
    public function handle(Company $company): array
    {
        $vatRate = $this->getProjectSettings->handle()->getVatRate();

        $balance = $company->balance(WalletType::CompanyWallet);

        $orderCost = $company->order_cost->multiply(($vatRate) + 1)->getAmount();

        return [
            'balance' => $balance,
            'availableOrders' => $orderCost ? $balance->divide($orderCost, Money::ROUND_DOWN)->getAmount() : 0,
        ];
    }
}

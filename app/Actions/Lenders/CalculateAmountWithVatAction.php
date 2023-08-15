<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\CalculateAmountWithVat;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Models\Company;
use Money\Money;

class CalculateAmountWithVatAction implements CalculateAmountWithVat
{
    public function __construct(
        protected GetProjectSettings $getProjectSettings
    ) {
    }

    /**
     * Update user.
     *
     * @return array $user
     */
    public function handle(Company $company, int $chargeAmount): array
    {
        $vatOfChargeAmount = $this->getVatAmount($chargeAmount);
        $vatOfOrderCost = $this->getVatAmount($company->order_cost);

        $chargeAmountWithoutVat = money($chargeAmount)->subtract($vatOfChargeAmount);

        $orderCost = $company->order_cost->add($vatOfOrderCost)->getAmount();
        $orderCount = \money($chargeAmount)->divide($orderCost, Money::ROUND_DOWN)->getAmount();

        return [$chargeAmountWithoutVat->getAmount(), $orderCount];
    }

    private function getVatAmount($amount): \Cknow\Money\Money
    {
        if (! ($amount instanceof \Cknow\Money\Money)) {
            $amount = money($amount);
        }

        $vatRate = $this->getProjectSettings->handle()->getVatRate();

        return $amount->multiply($vatRate);
    }
}

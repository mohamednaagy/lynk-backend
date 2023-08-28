<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\Lenders\CalcAmountWithoutVatAndOrdersCount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Models\Company;
use Cknow\Money\Money;

class CalcAmountWithoutVatAndOrdersCountAction implements CalcAmountWithoutVatAndOrdersCount
{
    public function __construct(
        protected GetProjectSettings $getProjectSettings,
        protected CalculateVatAmount $calculateVatAmount
    ) {
    }

    /**
     * Update user.
     *
     * @return array $user
     */
    public function handle(Company $company, Money $chargeAmountWithVat): array
    {
        [$vatOfChargeAmount, $vatRateOfChargeAmount] = $this->calculateVatAmount
            ->setAmount($chargeAmountWithVat)
            ->setIsVatIncludedInAmount(true)
            ->handle();

        [$vatOfOrderCost] = $this->calculateVatAmount
            ->setAmount($company->order_cost)
            ->setIsVatIncludedInAmount(false)
            ->setVatRate($vatRateOfChargeAmount)
            ->handle();

        $orderCost = $company->order_cost->add($vatOfOrderCost);
        $ordersCount = $chargeAmountWithVat->getAmount() / $orderCost->getAmount();

        $chargeAmountWithoutVat = $chargeAmountWithVat->subtract($vatOfChargeAmount);

        return [
            $chargeAmountWithoutVat,
            floor($ordersCount),
            $vatRateOfChargeAmount,
            $ordersCount,
        ];
    }
}

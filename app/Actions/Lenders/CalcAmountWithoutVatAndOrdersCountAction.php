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
        [$vatOfChargeAmount, $vatRate] = $this->calculateVatAmount
            ->setAmount($chargeAmountWithVat)
            ->setIsVatIncludedInAmount(true)
            ->handle();

        $ordersCount = null;
        if (! $company->isTiered()) {
            $tierPrice = $company->tieredPricing()->first();
            $orderCostWithVat = $tierPrice->order_cost_without_vat->multiply(($vatRate) + 1);
            $ordersCount = $chargeAmountWithVat->divide($orderCostWithVat->getAmount(), \Money\Money::ROUND_DOWN);
        }

        $chargeAmountWithoutVat = $chargeAmountWithVat->subtract($vatOfChargeAmount);

        return [$chargeAmountWithoutVat, $ordersCount?->getAmount()];
    }
}

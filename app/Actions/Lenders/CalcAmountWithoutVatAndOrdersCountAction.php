<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\Lenders\CalcAmountWithoutVatAndOrdersCount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Models\Lender;
use App\Models\TieredPricing;
use Cknow\Money\Money;

class CalcAmountWithoutVatAndOrdersCountAction implements CalcAmountWithoutVatAndOrdersCount
{
    public function __construct(
        protected GetProjectSettings $getProjectSettings,
        protected CalculateVatAmount $calculateVatAmount
    ) {}

    /**
     * Update user.
     *
     * @return array $user
     */
    public function handle(Lender $lender, Money $chargeAmountWithVat): array
    {
        [$vatOfChargeAmount, $vatRateOfChargeAmount] = $this->calculateVatAmount
            ->setAmount($chargeAmountWithVat)
            ->setIsVatIncludedInAmount(true)
            ->handle();

        $chargeAmountWithoutVat = $chargeAmountWithVat->subtract($vatOfChargeAmount);

        $ordersCount = $this->calcOrdersCount($lender, $chargeAmountWithVat);

        return [
            $chargeAmountWithoutVat,
            $ordersCount ? floor($ordersCount) : null,
            $vatRateOfChargeAmount,
            $ordersCount,
        ];
    }

    protected function calcOrdersCount(Lender $lender, Money $chargeAmountWithVat)
    {
        $ordersCount = null;
        if ($lender->isStandard()) {
            $orderCostWithVat = TieredPricing::getOrderCostIfStandard($lender)['costWithVat'];
            $ordersCount = $chargeAmountWithVat->getAmount() / $orderCostWithVat->getAmount();
        }

        return $ordersCount;
    }
}

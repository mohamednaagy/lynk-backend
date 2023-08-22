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
        [$vatOfChargeAmount] = $this->calculateVatAmount->handle($chargeAmountWithVat);
        [$vatOfOrderCost] = $this->calculateVatAmount
            ->setAmount($company->order_cost)
            ->setIsVatIncludedInAmount(false)
            ->handle();

        $orderCost = $company->order_cost->add($vatOfOrderCost)->formatByDecimal();
        $orderCount = $chargeAmountWithVat->divide($orderCost)->formatByDecimal();

        $chargeAmountWithoutVat = $chargeAmountWithVat->subtract($vatOfChargeAmount)->formatByDecimal();

        return [$chargeAmountWithoutVat, floor($orderCount)];
    }
}

<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Companies\CalculateVatAmount;
use App\Actions\Contracts\Lenders\CalculateAmountWithVat;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Models\Company;
use Cknow\Money\Money;

class CalculateAmountWithVatAction implements CalculateAmountWithVat
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
    public function handle(Company $company, int $chargeAmountWithVat): array
    {
        $chargeAmountWithVatMoney = Money::SAR($chargeAmountWithVat, true); // This casting to simulate the values from the database
        [$vatOfChargeAmount] = $this->calculateVatAmount->handle($chargeAmountWithVatMoney);
        [$vatOfOrderCost] = $this->calculateVatAmount->handle($company->order_cost);

        $orderCost = $company->order_cost->add($vatOfOrderCost)->formatByDecimal();
        $orderCount = $chargeAmountWithVatMoney->divide($orderCost)->formatByDecimal();

        $chargeAmountWithoutVat = $chargeAmountWithVatMoney->subtract($vatOfChargeAmount)->formatByDecimal();

        return [$chargeAmountWithoutVat, floor($orderCount)];
    }
}

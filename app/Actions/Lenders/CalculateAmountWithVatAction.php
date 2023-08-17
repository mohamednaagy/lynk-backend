<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Companies\GetVatAmount;
use App\Actions\Contracts\Lenders\CalculateAmountWithVat;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Models\Company;
use Cknow\Money\Money;

class CalculateAmountWithVatAction implements CalculateAmountWithVat
{
    public function __construct(
        protected GetProjectSettings $getProjectSettings,
        protected GetVatAmount $getVatAmount
    ) {
    }

    /**
     * Update user.
     *
     * @return array $user
     */
    public function handle(Company $company, int $chargeAmount): array
    {
        $chargeAmount = Money::SAR($chargeAmount, true); // This casting to simulate the values from the database
        [$vatOfChargeAmount] = $this->getVatAmount->handle($chargeAmount);
        [$vatOfOrderCost] = $this->getVatAmount->handle($company->order_cost);

        $orderCost = $company->order_cost->add($vatOfOrderCost)->formatByDecimal();
        $orderCount = $chargeAmount->divide($orderCost)->formatByDecimal();

        $chargeAmountWithoutVat = $chargeAmount->subtract($vatOfChargeAmount)->formatByDecimal();

        return [$chargeAmountWithoutVat, round($orderCount, mode: PHP_ROUND_HALF_DOWN)];
    }
}

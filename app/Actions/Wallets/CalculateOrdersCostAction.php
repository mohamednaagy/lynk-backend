<?php

namespace App\Actions\Wallets;

use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use Cknow\Money\Money;

class CalculateOrdersCostAction implements CalculateOrdersCost
{
    public function __construct(
        protected GetProjectSettings $getProjectSettings
    ) {
    }

    public function handle(int $ordersCount, Money $orderCostWithoutVat): Money
    {
        $vatRate = $this->getProjectSettings->handle()->getVatRate();

        $orderCostWithVat = $orderCostWithoutVat->multiply(($vatRate) + 1, \Money\Money::ROUND_HALF_DOWN);

        return $orderCostWithVat->multiply($ordersCount);
    }
}

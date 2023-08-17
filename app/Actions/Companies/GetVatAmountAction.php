<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetVatAmount;
use App\Actions\Contracts\ProjectSettings\GetProjectSettings;
use Cknow\Money\Money;

class GetVatAmountAction implements GetVatAmount
{
    public function __construct(
        protected GetProjectSettings $getProjectSettings
    ) {
    }

    public function handle(Money $amount): array
    {
        $vatRate = $this->getProjectSettings->handle()->getVatRate();
        $vatAmount = $amount->multiply($vatRate);

        return [$vatAmount, $vatRate];
    }
}

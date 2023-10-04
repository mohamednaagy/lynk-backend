<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CreateDefaultPricingTier;
use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Area;
use App\Enums\OrderFeeType;
use App\Models\Company;
use Cknow\Money\Money;

class CreateDefaultPricingTierAction implements CreateDefaultPricingTier
{
    public function __construct(
        protected GetSettingsClassInstance $getSettingsClassInstance
    ) {
    }

    public function handle(Company $company)
    {
        $orderCostWithoutVat = Money::parseByDecimal(
            $this->getSettingsClassInstance
                ->handle(Area::Lender)
                ->default_order_cost,
            Money::getDefaultCurrency()
        );

        $company->tieredPricing()->create([
            'order_value_start' => 0,
            'order_value_end' => null,
            'fee_type' => OrderFeeType::Fixed,
            'order_cost_without_vat' => $orderCostWithoutVat,
        ]);
    }
}

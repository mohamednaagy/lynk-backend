<?php

namespace App\Actions\Orders;

use App\Enums\FinancingOrderTypeEnum;

class FinancingOrderTypeFactory
{
    public function __construct(
        private NormalLendingFinancingOrderStrategy $normalLendingFinancingOrderStrategy,
        private SpecialPurposeVehicleFinancingOrderStrategy $specialPurposeVehicleFinancingOrderStrategy,
        private TimeDepositFinancingOrderStrategy $timeDepositFinancingOrderStrategy,
    ) {}

    public function make($financingOrderType): FinancingOrderTypeStrategy
    {
        return match ($financingOrderType) {
            FinancingOrderTypeEnum::NormalLending   => $this->normalLendingFinancingOrderStrategy,
            FinancingOrderTypeEnum::SpecialPurposeVehicle => $this->specialPurposeVehicleFinancingOrderStrategy,
            FinancingOrderTypeEnum::TimeDeposit    => $this->timeDepositFinancingOrderStrategy,
            default    => $this->normalLendingFinancingOrderStrategy, 
        };
    }
}
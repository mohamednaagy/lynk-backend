<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders\Validators;

use App\Enums\FinancingOrderTypeEnum;

class FinancingOrderTypeValidatorFactory
{
    public static function create(int $type): AbstractFinancingOrderTypeValidator
    {
        return match ($type) {
            FinancingOrderTypeEnum::NormalLending => new NormalLendingValidator,
            FinancingOrderTypeEnum::SpecialPurposeVehicle => new SpecialPurposeVehicleValidator,
            FinancingOrderTypeEnum::TimeDeposit => new TimeDepositValidator,
            default => new NormalLendingValidator,
        };
    }
}

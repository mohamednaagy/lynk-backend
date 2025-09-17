<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders\Validators;

use App\Enums\FinancingOrderTypeEnum;

class OrderTypeValidatorFactory
{
    public static function create(int $type, int $lenderId): AbstractOrderTypeValidator
    {
        return match ($type) {
            FinancingOrderTypeEnum::NormalLending => new NormalLendingValidator($lenderId),
            FinancingOrderTypeEnum::SpecialPurposeVehicle => new SpecialPurposeVehicleValidator($lenderId),
            FinancingOrderTypeEnum::TimeDeposit => new TimeDepositValidator($lenderId),
            default => new NormalLendingValidator($lenderId), 
        };
    }
}
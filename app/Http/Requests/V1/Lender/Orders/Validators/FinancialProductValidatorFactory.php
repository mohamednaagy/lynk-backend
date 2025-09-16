<?php

namespace App\Http\Requests\V1\Lender\Orders\Validators;

use App\Enums\FinancingOrderTypeEnum;

class FinancialProductValidatorFactory
{
    public static function create(int $type, int $lenderId): AbstractFinancialProductValidator
    {
        return match ($type) {
            FinancingOrderTypeEnum::NormalLending => new NormalLendingValidator($lenderId),
            FinancingOrderTypeEnum::SpecialPurposeVehicle => new SpecialPurposeVehicleValidator($lenderId),
            FinancingOrderTypeEnum::TimeDeposit => new TimeDepositValidator($lenderId),
            default => new NormalLendingValidator($lenderId), 
        };
    }
}
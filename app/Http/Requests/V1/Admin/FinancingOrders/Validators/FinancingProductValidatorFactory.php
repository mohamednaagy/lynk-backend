<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders\Validators;

use App\Enums\FinancingProductEnum;

class FinancingProductValidatorFactory
{
    public static function create(int $financingProductId): FinancingProductValidatorInterface
    {
        return match ($financingProductId) {
            FinancingProductEnum::SpecialPurposeVehicle => new SpecialPurposeVehicleValidator(),
            FinancingProductEnum::TimeDeposit => new TimeDepositValidator(),
            default => new NormalLendingValidator(), 
        };
    }
}
<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders\Validators;

use App\Enums\FinancialProductEnum;

class FinancialProductValidatorFactory
{
    public static function create(int $financialproductid, int $companyid): AbstractFinancialProductValidator
    {
        return match ($financialproductid) {
            FinancialProductEnum::NormalLending => new NormalLendingValidator($companyid),
            FinancialProductEnum::SpecialPurposeVehicle => new SpecialPurposeVehicleValidator($companyid),
            FinancialProductEnum::TimeDeposit => new TimeDepositValidator($companyid),
            default => new NormalLendingValidator($companyid), 
        };
    }
}
<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetOrderType;
use App\Enums\FinancingOrderTypeEnum;
use App\Models\Lender;

class GetOrderTypeAction implements GetOrderType
{
    public function handle(Lender $lender)
    {
        $allowedFinancialOrderTypes = $lender->allowedFinancialOrderTypes();

        return collect($allowedFinancialOrderTypes)->map(function ($value) {
            return [
                'id' => $value,                                 
                'name'   => FinancingOrderTypeEnum::getDescription($value),  
            ];
        })->values();
    }
}

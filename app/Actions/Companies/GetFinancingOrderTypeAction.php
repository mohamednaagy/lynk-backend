<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetFinancingOrderType;
use App\Enums\FinancingOrderTypeEnum;
use App\Models\Lender;

class GetFinancingOrderTypeAction implements GetFinancingOrderType
{
    public function handle(Lender $lender)
    {
        $allowedFinancingOrderTypes = $lender->allowedFinancingOrderTypes();

        return collect($allowedFinancingOrderTypes)->map(function ($value) {
            return [
                'id' => $value,                                 
                'name'   => FinancingOrderTypeEnum::getDescription($value),  
            ];
        })->values();
    }
}

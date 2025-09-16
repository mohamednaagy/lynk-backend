<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetFinancialProduct;
use App\Enums\FinancingOrderTypeEnum;
use App\Models\Lender;

class GetFinancialProductAction implements GetFinancialProduct
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

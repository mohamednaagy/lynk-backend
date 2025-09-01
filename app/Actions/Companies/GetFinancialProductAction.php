<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetFinancialProduct;
use App\Enums\FinancingProductEnum;
use App\Models\Company;

class GetFinancialProductAction implements GetFinancialProduct
{
    public function handle(Company $lender)
    {
        $allowed = $lender->lender->lenderDetail->allowed_financing_products;

        return collect($allowed)->map(function ($value) {
            return [
                'id' => $value,                                 
                'name'   => FinancingProductEnum::getDescription($value),  
            ];
        })->values();
    }
}

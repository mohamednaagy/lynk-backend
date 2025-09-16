<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\GetFinancialProduct;
use App\Enums\FinancialProductEnum;
use App\Models\Company;

class GetFinancialProductAction implements GetFinancialProduct
{
    public function handle(Company $lender)
    {
        $allowed = $lender->lender->lenderDetail->allowed_financial_products;

        return collect($allowed)->map(function ($value) {
            return [
                'id' => $value,                                 
                'name'   => FinancialProductEnum::getDescription($value),  
            ];
        })->values();
    }
}

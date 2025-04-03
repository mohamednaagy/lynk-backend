<?php

namespace App\Transformers;

use App\Models\Company;
use League\Fractal\TransformerAbstract;

class CompanySettingTransformer extends TransformerAbstract
{
    public function transform(Company $company): array
    {
        return [
            'id' => $company->id,
            'does_order_require_approval' => $company->lender->lenderDetail->does_order_require_approval,
            'trading_mode' => $company->lender->lenderDetail->trading_mode,
        ];
    }
}

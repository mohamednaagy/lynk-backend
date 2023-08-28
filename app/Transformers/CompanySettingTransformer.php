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
            'does_order_require_approval' => $company->does_order_require_approval,
            'order_cost' => $company->order_cost,
            'trading_mode' => $company->trading_mode,
        ];
    }
}

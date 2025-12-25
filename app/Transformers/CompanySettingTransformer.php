<?php

namespace App\Transformers;

use App\Models\Lender;
use League\Fractal\TransformerAbstract;

class CompanySettingTransformer extends TransformerAbstract
{
    public function transform(Lender $lender): array
    {
        return [
            'id' => $lender->id,
            'does_order_require_approval' => $lender->lenderDetail->does_order_require_approval,
            'trading_mode' => $lender->lenderDetail->trading_mode,
        ];
    }
}

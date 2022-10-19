<?php

namespace App\Transformers;

use App\Models\FinancingOrder;
use League\Fractal\TransformerAbstract;

class FinancingOrderTransformer extends TransformerAbstract
{
    public function transform(FinancingOrder $financingOrder)
    {
        return [
            'id' => $financingOrder->id,
            'status' => $financingOrder->status->description,
            'company_id' => $financingOrder->company_id,
            'reference_number' => $financingOrder->reference_number,
            'national_id' => $financingOrder->national_id,
            'amount' => $financingOrder->amount,
            'selling_price' => $financingOrder->selling_price,
            'contract' => $financingOrder->contract,
            'power_of_attorney' => $financingOrder->power_of_attorney,
        ];
    }
}

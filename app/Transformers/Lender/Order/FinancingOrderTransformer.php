<?php

namespace App\Transformers\Lender\Order;

use App\Models\FinancingOrder;
use App\Transformers\FinancingOrderTransformer as BaseFinancingOrderTransformer;

class FinancingOrderTransformer extends BaseFinancingOrderTransformer
{
    public function includeCommodityType(FinancingOrder $financingOrder)
    {
        $commodityType = $financingOrder->commodityType;
        if (! $commodityType) {
            return $this->primitive(null);
        }

        return $this->primitive([
            'id' => $commodityType->unique_name,
            'name' => $commodityType->name,
        ]);
    }
}

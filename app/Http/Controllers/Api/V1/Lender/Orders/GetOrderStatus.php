<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;

class GetOrderStatus extends Controller
{
    public function __invoke()
    {
        $OrdersStatus = array_map(function ($value) {
            return [
                'key' => FinancingOrderStatus::getKey($value),
                'value' => $value,
                'description' => FinancingOrderStatus::getDescription($value),
            ];
        }, FinancingOrderStatus::getValues());

        return $this->successResponse($OrdersStatus);
    }
}

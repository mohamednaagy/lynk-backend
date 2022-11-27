<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\CalculateOrdersRequest;
use Illuminate\Http\JsonResponse;

class CalculateOrderCost extends Controller
{
    public function __invoke(
        CalculateOrdersRequest $calculateOrdersRequest,
        CalculateOrdersCost $calculateOrdersCost
    ): JsonResponse {
        $response = $calculateOrdersCost->handle(
            ordersCount: $calculateOrdersRequest->validated('orders_count'),
            orderCost: tenant()->order_cost
        );

        return $this->successResponse(data: [
            'amount' => $response,
        ]);
    }
}

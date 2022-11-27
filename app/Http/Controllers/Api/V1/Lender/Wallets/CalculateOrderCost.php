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
            // __REVIEW__ use validated(...) instead of input(...)
            ordersCount: $calculateOrdersRequest->input('orders_count'),
            orderCost: tenant()->order_cost
        );

        return $this->successResponse(data: [
            'amount' => $response,
        ]);
    }
}

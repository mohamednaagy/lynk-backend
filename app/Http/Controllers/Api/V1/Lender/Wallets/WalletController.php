<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\CalculateOrdersRequest;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function calculateOrders(
        CalculateOrdersRequest $calculateOrdersRequest,
        CalculateOrdersCost $calculateOrdersCost
    ): JsonResponse {
        $response = $calculateOrdersCost->handle([
            'orders' => $calculateOrdersRequest->get('orders'),
            'order_cost' => auth()->user()->company->order_cost,
        ]);

        return $this->successResponse(data: [
            'amount' => $response,
        ]);
    }
}

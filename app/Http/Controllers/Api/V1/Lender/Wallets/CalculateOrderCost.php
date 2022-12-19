<?php

namespace App\Http\Controllers\Api\V1\Lender\Wallets;

use App\Actions\Contracts\Wallets\CalculateOrdersCost;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Wallets\CalculateOrdersRequest;
use Illuminate\Http\JsonResponse;

class CalculateOrderCost extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Lender, [Subject::LenderFinancingOrderCost, Action::Calculate])
        );
    }

    public function __invoke(
        CalculateOrdersRequest $request,
        CalculateOrdersCost $calculateOrdersCost
    ): JsonResponse {
        $amount = $calculateOrdersCost->handle(
            ordersCount: $request->validated('orders_count'),
            orderCost: tenant()->order_cost
        );

        return $this->successResponse(data: [
            'amount' => $amount->formatByDecimal(),
        ]);
    }
}

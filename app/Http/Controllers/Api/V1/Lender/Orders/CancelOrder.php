<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CancelOrder as CancelOrderInterface;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\CancelOrderRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;

class CancelOrder extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  CancelOrderRequest  $cancelOrderRequest
     * @param  CancelOrderInterface  $cancelOrder,
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function __invoke(CancelOrderRequest $cancelOrderRequest, CancelOrderInterface $cancelOrder, FinancingOrder $order): JsonResponse
    {
        if ($order->status->is(FinancingOrderStatus::PendingApproval || FinancingOrderStatus::Rejected)) {
            $order->update(['status' => 3]);
        }

        $cancelOrder->handle(
            $order,
            $cancelOrderRequest->user(),
            $cancelOrderRequest->validated()
        );

        return $this->successResponse();
    }
}

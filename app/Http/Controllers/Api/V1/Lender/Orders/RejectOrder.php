<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\RejectOrder as RejectOrderInterface;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\RejectOrderRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;

class RejectOrder extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  RejectOrderRequest  $rejectOrderRequest
     * @param  RejectOrderInterface  $rejectOrder
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function __invoke(
        RejectOrderRequest $rejectOrderRequest,
        RejectOrderInterface $rejectOrder,
        FinancingOrder $order
    ): JsonResponse {
        if ($order->status->cantMoveTo(FinancingOrderStatus::Rejected)) {
            return $this->errorResponse(
                __('error.order_cannot_be_approved_because_it_is_approved')
            );
        }

        $rejectOrder->handle($order, $rejectOrderRequest->user(), $rejectOrderRequest->validated());

        return $this->successResponse();
    }
}

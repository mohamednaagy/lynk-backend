<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CancelOrder as CancelOrderInterface;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\CancelOrderRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CancelOrder extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  CancelOrderRequest  $cancelOrderRequest
     * @param  CancelOrderInterface  $cancelOrder ,
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function __invoke(
        CancelOrderRequest $cancelOrderRequest,
        CancelOrderInterface $cancelOrder,
        int $order
    ): JsonResponse {
        return DB::transaction(function () use ($cancelOrderRequest, $cancelOrder, $order) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);

            if ($order->status->cantMoveTo(FinancingOrderStatus::Cancelled)) {
                return $this->errorResponse(
                    __('error.unable_to_cancelled'),
                    ErrorCode::UNABLE_TO_CANCEL_ORDER
                );
            }

            $cancelOrder->handle(
                $order,
                $cancelOrderRequest->user(),
                $cancelOrderRequest->validated()
            );

            return $this->successResponse();
        });
    }
}

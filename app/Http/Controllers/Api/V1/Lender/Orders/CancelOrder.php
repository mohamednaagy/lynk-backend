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
        CancelOrderRequest $request,
        CancelOrderInterface $cancelOrder,
        int $order
    ): JsonResponse {
        return DB::transaction(function () use ($request, $cancelOrder, $order) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);

            if ($order->status->cantMoveTo(FinancingOrderStatus::PendingCancellation)) {
                return $this->errorResponse(
                    __('error.unable_to_cancel_order'),
                    ErrorCode::UNABLE_TO_CANCEL_ORDER
                );
            }

            $cancelOrder->handle(
                $order,
                $request->user(),
                $request->validated()
            );

            return $this->successResponse();
        });
    }
}

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
    // __REVIEW__ break down the arguments on lines to be easier to read
    public function __invoke(RejectOrderRequest $rejectOrderRequest, RejectOrderInterface $rejectOrder, FinancingOrder $order): JsonResponse
    {
        // __REVIEW__ use $order->status->cantMoveTo(...)
        // See app/Http/Controllers/Api/V1/Lender/Orders/MakeOrderProceed.php for reference
        if (! $order->status->is(FinancingOrderStatus::PendingApproval)) {
            // __REVIEW__ change error message and add error code as in MakeOrderProceed
            return $this->errorResponse(
                __('error.order_cannot_be_approved_because_it_is_approved')
            );
        }

        $rejectOrder->handle($order, $rejectOrderRequest->user(), $rejectOrderRequest->validated());

        return $this->successResponse();
    }
}

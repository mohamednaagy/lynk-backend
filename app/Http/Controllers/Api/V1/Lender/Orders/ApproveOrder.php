<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\ApproveOrder as ApproveOrderInterface;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApproveOrder extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  ApproveOrderInterface  $approveOrder
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function __invoke(Request $request, ApproveOrderInterface $approveOrder, FinancingOrder $order)
    {
        if ($order->status->cantMoveTo(FinancingOrderStatus::Approved)) {
            return $this->errorResponse(
                __('error.order_cannot_be_approved_because_it_is_approved'),
                Response::HTTP_BAD_REQUEST
            );
        }

        $approveOrder->handle($order, $request->user());

        return $this->successResponse();
    }
}

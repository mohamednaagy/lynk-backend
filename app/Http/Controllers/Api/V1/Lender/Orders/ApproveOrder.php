<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\ApproveOrder as ApproveOrderInterface;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\FinancingOrder;
use Symfony\Component\HttpFoundation\Response;

class ApproveOrder extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ApproveOrderInterface $approveOrder, FinancingOrder $order)
    {
        if (! $order->status->is(FinancingOrderStatus::PendingApproval)) {
            return $this->errorResponse(
                __('error.order_cannot_be_approved_because_it_is_approved', ['status' => $order->status->description]),
                Response::HTTP_BAD_REQUEST
            );
        }

        $approveOrder->handle($order, auth()->user());

        return $this->successResponse();
    }
}

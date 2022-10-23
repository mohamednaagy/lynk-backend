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
            return $this->errorResponse(__('The order status is '.$order->status->description), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $approveOrder->handle($order, auth()->user());

        return $this->successResponse();
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\ApproveOrder as ApproveOrderInterface;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\ApproveOrderRequest;
use App\Models\FinancingOrder;
use App\Notifications\OrderApproved;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ApproveOrder extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  ApproveOrderRequest  $approveOrderRequest
     * @param  ApproveOrderInterface  $approveOrder
     * @param  int  $order
     * @return JsonResponse
     */
    public function __invoke(ApproveOrderRequest $approveOrderRequest, ApproveOrderInterface $approveOrder, int $order): JsonResponse
    {
        return DB::transaction(function () use ($approveOrderRequest, $approveOrder, $order) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);
            if ($order->status->cantMoveTo(FinancingOrderStatus::Approved)) {
                return $this->errorResponse(
                    __('error.order_cannot_be_approved_because_it_is_approved'),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $data = $approveOrderRequest->validated();

            $approveOrder->handle($order, $approveOrderRequest->user());

            if ($order->creator->id !== $approveOrderRequest->user()->id) {
                $order->creator->notify(new OrderApproved($order, $approveOrderRequest->user(), $data['redirect_url']));
            }

            return $this->successResponse();
        });
    }
}

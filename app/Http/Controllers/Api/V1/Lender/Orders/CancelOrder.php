<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CancelOrder as CancelOrderInterface;
use App\Enums\ErrorCode;
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
     * @param  CancelOrderInterface  $cancelOrder ,
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function __invoke(CancelOrderRequest $cancelOrderRequest, CancelOrderInterface $cancelOrder, FinancingOrder $order): JsonResponse
    {
        if (
            ! in_array($order->status->value,
                [
                    FinancingOrderStatus::Rejected,
                    FinancingOrderStatus::Approved,
                    FinancingOrderStatus::RespondedToPtp,
                    FinancingOrderStatus::PendingApproval,
                    FinancingOrderStatus::CommodityPurchased,
                    FinancingOrderStatus::WaitingClientWakala,
                    FinancingOrderStatus::PtpDocumentRetrieved,
                    FinancingOrderStatus::ClientWakalaCompleted,
                    FinancingOrderStatus::CommoditySoldToCustomer,
                    FinancingOrderStatus::WaitingPurchasingCommodity,
                ]
            )
        ) {
            return $this->errorResponse(
                trans('unable_to_cancelled'),
                ErrorCode::UNABLE_TO_CANCEL_ORDER
            );
        }

        $cancelOrder->handle(
            $order,
            $cancelOrderRequest->user(),
            $cancelOrderRequest->validated()
        );

        return $this->successResponse();
    }
}

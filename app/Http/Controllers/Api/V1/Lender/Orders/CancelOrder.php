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
                    FinancingOrderStatus::InProgress,
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
            return $this->errorResponse('This order can\'t be cancelled');
        }

        $cancelOrder->handle(
            $order,
            $cancelOrderRequest->user(),
            $cancelOrderRequest->validated()
        );

        return $this->successResponse();
    }
}

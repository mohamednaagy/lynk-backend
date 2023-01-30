<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Actions\Contracts\Admins\Lenders\Orders\ProceedSellingCommodity as ProceedSellingCommodityInterface;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProceedSellingCommodity extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Company  $lender
     * @param  FinancingOrder  $order
     * @param  ProceedSellingCommodityInterface  $proceedSellingCommodity
     * @return JsonResponse
     */
    public function __invoke(Company $lender, FinancingOrder $order, ProceedSellingCommodityInterface $proceedSellingCommodity)
    {
        if ($order->status->cantMoveTo(FinancingOrderStatus::CommoditySoldToCustomer)) {
            return $this->errorResponse(
                __('error.order_status_doesnt_follow_sequence'),
                Response::HTTP_BAD_REQUEST,
                ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE
            );
        }

        $proceedSellingCommodity->handle($order);

        return $this->successResponse([]);
    }
}

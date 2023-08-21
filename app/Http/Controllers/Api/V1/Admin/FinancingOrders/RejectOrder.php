<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\RejectOrder as RejectOrderInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\RejectOrderRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RejectOrder extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Reject, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(
        RejectOrderRequest $rejectOrderRequest,
        RejectOrderInterface $rejectOrder,
        int $order
    ): JsonResponse {
        return DB::transaction(function () use ($rejectOrderRequest, $rejectOrder, $order) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);

            if ($order->status->cantMoveTo(FinancingOrderStatus::Rejected)) {
                return $this->errorResponse(
                    __('error.order_cannot_be_approved_because_it_is_approved')
                );
            }

            $rejectOrder->handle($order, $rejectOrderRequest->user(), $rejectOrderRequest->validated());

            return $this->successResponse();
        });
    }
}

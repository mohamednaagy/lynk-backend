<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CancelTraderOrder as CancelTraderOrderInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\Subject;
use App\Enums\TraderOrderCancelReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\CancelOrderRequest;
use App\Jobs\FinancingOrders\NotifyAboutTraderOrderCancelled;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CancelTraderOrder extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::FinancingOrders, Action::Manage, Action::Cancel])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  CancelTraderOrderInterface  $cancelTraderOrder  ,
     *
     * @throws \Throwable
     */
    public function __invoke(
        CancelOrderRequest $request,
        CancelTraderOrderInterface $cancelTraderOrder,
        int $order,
        int $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $cancelTraderOrder, $traderOrder) {
            $traderOrder = TraderOrder::lockForUpdate()->findOrFail($traderOrder);

            if (! $traderOrder->canBeCancelled()) {
                return $this->errorResponse(
                    __('error.unable_to_cancel_order'),
                    Response::HTTP_FORBIDDEN,
                    ErrorCode::UNABLE_TO_CANCEL_ORDER
                );
            }

            if ($traderOrder->doesLastActionMatchWith(FinancingOrderHistory::ContractSigned)) {
                return $this->errorResponse(
                    __('error.unable_to_cancel_order'),
                    Response::HTTP_FORBIDDEN,
                    ErrorCode::UNABLE_TO_CANCEL_ORDER
                );
            }

            $canceller = $request->user();
            $cancelTraderOrder->handle(
                $traderOrder,
                $canceller,
                $request->validated(),
                TraderOrderCancelReason::TraderOrderIsCancelled
            );

            // test pre-commit hook
            dispatch(new NotifyAboutTraderOrderCancelled($traderOrder, $canceller));

            return $this->successResponse();
        });
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Actions\Contracts\Orders\CancelTraderOrder as CancelTraderOrderInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\Subject;
use App\Enums\Trader;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\CancelOrderRequest;
use App\Jobs\FinancingOrders\NotifyAdminAndLenderAboutTraderOrderCancelled;
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
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Cancel])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  CancelTraderOrderInterface  $cancelTraderOrder  ,
     */
    public function __invoke(
        CancelOrderRequest $request,
        CancelTraderOrderInterface $cancelTraderOrder,
        int $order,
        int $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $cancelTraderOrder, $traderOrder) {
            $traderOrder = TraderOrder::lockForUpdate()->findOrFail($traderOrder);

            if ($traderOrder->provider == Trader::Lynk) {
                if ($traderOrder->status->isNot(TraderOrderStatus::InProgress) && $traderOrder->status->isNot(TraderOrderStatus::Initiated)) {
                    return $this->errorResponse(
                        __('error.unable_to_cancel_order'),
                        Response::HTTP_FORBIDDEN,
                        ErrorCode::UNABLE_TO_CANCEL_ORDER
                    );
                }
            } else {
                if ($traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
                    return $this->errorResponse(
                        __('error.unable_to_cancel_order'),
                        Response::HTTP_FORBIDDEN,
                        ErrorCode::UNABLE_TO_CANCEL_ORDER
                    );
                }
            }

            $canceller = $request->user();
            $cancelTraderOrder->handle(
                $traderOrder,
                $canceller,
                $request->validated(),
                TraderOrderCancelReason::TraderOrderIsCancelled
            );

            dispatch(new NotifyAdminAndLenderAboutTraderOrderCancelled($traderOrder, $canceller));

            return $this->successResponse();
        });
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Actions\Contracts\Orders\CancelOrder as CancelOrderInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\CancelOrderRequest;
use App\Jobs\FinancingOrders\NotifyAdminAndLenderAboutOrderCancelled;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CancelOrder extends Controller
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
     * @param  CancelOrderRequest  $request
     * @param  CancelOrderInterface  $cancelOrder ,
     * @param  int  $order
     * @return JsonResponse
     */
    public function __invoke(
        CancelOrderRequest $request,
        CancelOrderInterface $cancelOrder,
        int $order
    ): JsonResponse {
        return DB::transaction(function () use ($request, $cancelOrder, $order) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);

            if ($order->status->cantMoveTo(FinancingOrderStatus::PendingCancellation)) {
                return $this->errorResponse(
                    __('error.unable_to_cancel_order'),
                    Response::HTTP_FORBIDDEN,
                    ErrorCode::UNABLE_TO_CANCEL_ORDER
                );
            }

            $canceller = $request->user();

            $cancelOrder->handle(
                $order,
                $canceller,
                $request->validated()
            );

            $order->update([
                'status' => FinancingOrderStatus::PendingCancellation,
                'status_reason' => $data['status_reason'] ?? null,
            ]);

            dispatch(new NotifyAdminAndLenderAboutOrderCancelled($order, $canceller));

            return $this->successResponse();
        });
    }
}

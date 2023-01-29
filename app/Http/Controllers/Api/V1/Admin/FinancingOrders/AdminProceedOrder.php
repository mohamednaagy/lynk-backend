<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Companies\Lenders\Orders\AdminProceedOrderRequest;
use App\Models\Company;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AdminProceedOrder extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Proceed, Action::Index])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(
        AdminProceedOrderRequest $request,
        Company $lender,
        int $order,
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);
            if ($request->validated('case') === FinancingOrderProceedCase::ClientWakalaAccepted) {
                return $this->handleClientWakalaAccepted($order);
            } elseif ($request->validated('case') === FinancingOrderProceedCase::ContractSigned) {
                return $this->handleContractSigned($order);
            }

            return $this->successResponse();
        });
    }

    public function handleClientWakalaAccepted(FinancingOrder $order)
    {
        if (
            $order->is_verification_required
            || $order->status->cantMoveTo(FinancingOrderStatus::ClientWakalaCompleted)
        ) {
            return $this->errorResponse(
                __('error.order_status_doesnt_follow_sequence'),
                Response::HTTP_BAD_REQUEST,
                ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE
            );
        }

        $media = app(AcceptClientWakala::class)->handle($order);

        $order->update([
            'status' => FinancingOrderStatus::ClientWakalaCompleted,
        ]);

        return $this->successResponse([
            'wakala_file_url' => route('api.v1.media.download', ['media' => $media->uuid]),
        ]);
    }

    public function handleContractSigned(FinancingOrder $order)
    {
        if ($order->status->cantMoveTo(FinancingOrderStatus::ContractSigned)) {
            return $this->errorResponse(
                __('error.order_status_doesnt_follow_sequence'),
                Response::HTTP_BAD_REQUEST,
                ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE
            );
        }

        $order->update([
            'status' => FinancingOrderStatus::ContractSigned,
        ]);

        $traderOrder = $order->activeTraderOrder()->first();

        $traderOrder->traderHistories()->create([
            'action' => FinancingOrderHistory::ContractSigned,
        ]);

        return $this->successResponse();
    }
}

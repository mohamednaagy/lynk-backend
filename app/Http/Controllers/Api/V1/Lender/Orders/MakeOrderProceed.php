<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Clients\AcceptClientWakala;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\MakeOrderProceedRequest;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

class MakeOrderProceed extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::FinancingOrders, Action::Proceed, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  MakeOrderProceedRequest  $request
     * @param  AcceptClientWakala  $acceptClientWakala
     * @param  int  $order
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function __invoke(
        MakeOrderProceedRequest $request,
        int $order
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);

            $this->authorize('view', $order);

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
            'status' => FinancingOrderStatus::WaitingPurchasingCommodity,
        ]);

        Trader::driver(config('trader.default'))->getTti($order);

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

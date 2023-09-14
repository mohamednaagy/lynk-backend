<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CancelTraderOrder as CancelTraderOrderInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\MurabhaStep;
use App\Enums\Subject;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\CancelOrderRequest;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
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
     * @param  CancelTraderOrderInterface  $cancelTraderOrder ,
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

            if ($traderOrder->status->isNot(TraderOrderStatus::InProgress)) {
                return $this->errorResponse(
                    __('error.unable_to_cancel_order'),
                    Response::HTTP_FORBIDDEN,
                    ErrorCode::UNABLE_TO_CANCEL_ORDER
                );
            }

            $lastHistoryOfContractSignedStep = $this->getContractSignedLastHistory($traderOrder);

            if ($traderOrder->checkOrderHistoryAction($lastHistoryOfContractSignedStep)) {
                return $this->errorResponse(
                    __('error.unable_to_cancel_order'),
                    Response::HTTP_FORBIDDEN,
                    ErrorCode::UNABLE_TO_CANCEL_ORDER
                );
            }

            $cancelTraderOrder->handle(
                $traderOrder,
                $request->user(),
                $request->validated(),
                TraderOrderCancelReason::Manual
            );

            return $this->successResponse();
        });
    }

    private function getContractSignedLastHistory($traderOrder)
    {
        $contractSignedStep = MurabhaStep::ContractSigned;

        $contractSignedHistories = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
            ->getStepOf($contractSignedStep)
            ?->histories;

        return end($contractSignedHistories);
    }
}

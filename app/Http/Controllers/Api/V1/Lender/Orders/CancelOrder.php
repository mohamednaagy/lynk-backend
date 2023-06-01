<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CancelOrder as CancelOrderInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\BursamMurabhaStep;
use App\Enums\DmccMurabhaStep;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\CancelOrderRequest;
use App\Models\FinancingOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CancelOrder extends Controller
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
     * @param  CancelOrderRequest  $request
     * @param  CancelOrderInterface  $cancelOrder ,
     * @param  int  $order
     * @return JsonResponse
     *
     * @throws \Throwable
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

            $traderOrder = $order->activeTraderOrder()->first();
            if ($traderOrder) {
                $lastHistoryOfContractSignedStep = $this->getContractSignedLastHistory($traderOrder);

                if ($traderOrder->checkOrderHistoryAction($lastHistoryOfContractSignedStep)) {
                    return $this->errorResponse(
                        __('error.unable_to_cancel_order'),
                        Response::HTTP_FORBIDDEN,
                        ErrorCode::UNABLE_TO_CANCEL_ORDER
                    );
                }
            }

            $cancelOrder->handle(
                $order,
                $request->user(),
                $request->validated()
            );

            return $this->successResponse();
        });
    }

    private function getContractSignedLastHistory($traderOrder)
    {
        $contractSignedStep = match ($traderOrder->provider) {
            'dmcc', 'fake' => DmccMurabhaStep::ContractSigned,
            'bursam' => BursamMurabhaStep::ContractSigned,
        };

        $contractSignedHistories = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
            ->getStepOf($contractSignedStep)
            ?->histories;

        return end($contractSignedHistories);
    }
}

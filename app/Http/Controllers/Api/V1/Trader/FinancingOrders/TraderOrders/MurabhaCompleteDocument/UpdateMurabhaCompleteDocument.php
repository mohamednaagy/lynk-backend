<?php

namespace App\Http\Controllers\Api\V1\Trader\FinancingOrders\TraderOrders\MurabhaCompleteDocument;

use App\Actions\Contracts\Orders\TraderOrders\MurabhaCompleteDocument\HandleMurabhaCompleteDocument;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Orders\MurabhaCompleteDocument\UpdateMurabhaCompleteDocumentRequest;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdateMurabhaCompleteDocument extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::Trader, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  UpdateMurabhaCompleteDocumentRequest  $request
     * @param  int  $order
     * @param  TraderOrder  $traderOrder
     * @return JsonResponse
     */
    public function __invoke(
        UpdateMurabhaCompleteDocumentRequest $request,
        int $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $traderOrder) {
            $order = FinancingOrder::lockForUpdate()->findOrFail($order);
            $orderStepComplete = $traderOrder->checkOrderStepComplete(FinancingOrderStatus::MurabahaSaleCompleted);

            if (
                $order->status->cantMoveTo(FinancingOrderStatus::MurabahaSaleCompleted) &&
                ! $orderStepComplete
            ) {
                throw new OrderStatusDoesNotFollowSequenceException();
            }

            app(HandleMurabhaCompleteDocument::class)->handle($request, $order, $traderOrder);

            return $this->successResponse();
        });
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrderSettlement;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\TraderOrder;
use App\Transformers\TraderOrderSettlementTransformer;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckSettlement extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage])
        );
    }

    /**
     * Handle the incoming request to check if a trader order's commodities are settled.
     */
    public function __invoke(
        InitiateTraderOrderSettlement $initiateTraderOrderSettlement,
        int $order,
        int $traderOrder
    ): JsonResponse {
        $traderOrder = TraderOrder::whereId($traderOrder)
            ->whereFinancingOrderId($order)->firstOrFail();

        if (! $traderOrder->canBeSettled()) {
            return $this->errorResponse(
                __('error.unable_to_settle_order'),
                Response::HTTP_BAD_REQUEST,
                ErrorCode::UNABLE_TO_SETTLE_ORDER
            );
        }

        $traderOrderSettlement = $initiateTraderOrderSettlement->handle($traderOrder);

        if (! $traderOrderSettlement) {
            return $this->errorResponse(__('error.pending_settlement_check'));
        }

        return fractal($traderOrderSettlement, new TraderOrderSettlementTransformer)
            ->parseIncludes(['is_commodities_settled', 'message', 'creator'])
            ->respond();
    }
}

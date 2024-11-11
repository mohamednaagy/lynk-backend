<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Actions\Contracts\Orders\GetOrderAndTraderOrderLockedForUpdate;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\UpdateSellConfirmationDocumentRequest;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdateSellConfirmationDocument extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     *
     * @throws \Throwable
     */
    public function __invoke(
        UpdateSellConfirmationDocumentRequest $request,
        int $order,
        int $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $traderOrder) {
            [$order, $traderOrder] = app(GetOrderAndTraderOrderLockedForUpdate::class)->handle($traderOrder);

            (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))->updateSellConfirmationDocument($traderOrder, $request);

            return $this->successResponse();
        });
    }
}
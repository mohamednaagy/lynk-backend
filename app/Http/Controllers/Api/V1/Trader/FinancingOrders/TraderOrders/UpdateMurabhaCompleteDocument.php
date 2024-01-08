<?php

namespace App\Http\Controllers\Api\V1\Trader\FinancingOrders\TraderOrders;

use App\Actions\Contracts\Orders\GetOrderAndTraderOrderLockedForUpdate;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Orders\MurabhaCompleteDocument\UpdateMurabhaCompleteDocumentRequest;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
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
     */
    public function __invoke(
        UpdateMurabhaCompleteDocumentRequest $request,
        int $order,
        int $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($request, $order, $traderOrder) {
            [$order, $traderOrder] = app(GetOrderAndTraderOrderLockedForUpdate::class)->handle($traderOrder);

            (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))->updateMurabhaCompleteDocument($traderOrder, $request);

            return $this->successResponse();
        });
    }
}

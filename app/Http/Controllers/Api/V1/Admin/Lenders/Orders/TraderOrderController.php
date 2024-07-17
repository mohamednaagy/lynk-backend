<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Actions\Contracts\Orders\CreateTraderOrder;
use App\Actions\Contracts\Orders\DeductBalanceForNewOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Enums\Trader;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\StoreTradingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TraderOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        )
            ->only(['store']);
    }

    /**
     * Handle the incoming request.
     */
    public function store(
        StoreTradingRequest $request,
        CreateTraderOrder $createTraderOrder,
        int $order
    ): JsonResponse {
        return DB::multipleTransaction(function () use ($request, $createTraderOrder, $order) {
            $data = $request->validated();
            $data['version'] = get_latest_version_of_trader($data['trader']);

            if (! $this->isModeAvailableForTrader($data['trader'], $data['mode'], $data['version'])) {
                return $this->errorResponse(__('error.trader_mode_not_supported'));
            }

            $traderOrder = $createTraderOrder->handle($order, $data);
            //Skip "LYNK" requests from create transaction at initiated step
            $traderOrder->handleDeductBalanceForNewOrder();

            return $this->successResponse();
        });
    }

    private function isModeAvailableForTrader($trader, $mode, $version): bool
    {
        $availableModes = config("trader.providers.{$trader}.modes.{$version}", []);

        return in_array($mode, $availableModes);
    }
}

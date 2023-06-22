<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Actions\Contracts\Orders\CreateTraderOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\StoreTradingRequest;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($request, $createTraderOrder, $order) {
            $data = $request->validated();
            $data['version'] = get_latest_version_of_trader($data['trader']);

            if (! $this->isModeAvailableForTrader($data['trader'], $data['mode'], $data['version'])) {
                return $this->errorResponse(__('error.trader_mode_not_supported'));
            }

            DB::transaction(function () use ($data, $order, $createTraderOrder) {
                $financingOrder = FinancingOrder::query()
                    ->lockForUpdate()
                    ->findOrFail($order);

                $createTraderOrder->handle($order, $data);

                $financingOrder->update([
                    'status' => FinancingOrderStatus::InProgress,
                ]);
            });

            return $this->successResponse();
        });
    }

    private function isModeAvailableForTrader($trader, $mode, $version): bool
    {
        $availableModes = config("trader.providers.{$trader}.modes.{$version}", []);

        return in_array($mode, $availableModes);
    }
}

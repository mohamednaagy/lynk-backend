<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Actions\Contracts\Orders\CreateTraderOrder;
use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Enums\TraderOrderMode as TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\StoreTradingRequest;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\UpdateTradingRequest;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
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
     *
     * @param  StoreTradingRequest  $request
     * @param  CreateTraderOrder  $createTraderOrder
     * @param  int  $order
     * @return JsonResponse
     */
    public function store(
        StoreTradingRequest $request,
        CreateTraderOrder $createTraderOrder,
        int $order
    ): JsonResponse {
        return DB::transaction(function () use ($request, $createTraderOrder, $order) {
            $data = $request->validated();
            $data['version'] = get_latest_version_of_trader($data['trader']);

            if (! $this->isModeAvailableForTrader($data['trader'], $data['mode'])) {
                return $this->errorResponse('This Mode Not Available For'.$data['trader'].' Trader.');
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

    public function update(
        UpdateTradingRequest $request,
        UpdateTraderOrder $updateTraderOrder,
        int $order,
        int $traderOrder,
    ): JsonResponse {
        return DB::transaction(function () use ($request, $updateTraderOrder, $traderOrder) {
            $data = $request->validated();

            $traderOrder = TraderOrder::query()
                ->lockForUpdate()
                ->findOrFail($traderOrder);

            if (! $this->isModeAvailableForTrader($traderOrder->provider, $data['mode'])) {
                return $this->errorResponse("This Mode Not Available For $traderOrder->provider Trader.");
            }

            if ($data['mode'] == TraderOrderMode::Automatic) {
                unset($data['reference_number']);
            }

            $updateTraderOrder->handle($traderOrder, $data);

            $traderOrder->update([
                'status' => TraderOrderStatus::InProgress,
            ]);

            return $this->successResponse();
        });
    }

    private function isModeAvailableForTrader($trader, $mode): bool
    {
        $availableModes = match ($trader) {
            'bursam' => [
                TraderOrderMode::Manual,
                TraderOrderMode::Automatic,
            ],
            'dmcc', 'fake' => [
                TraderOrderMode::Manual,
            ],
            default => []
        };

        return in_array($mode, $availableModes);
    }
}

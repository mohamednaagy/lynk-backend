<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Actions\Contracts\Orders\CreateTraderOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\StoreTradingRequest;
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
            if ($data['version'] == 'latest') {
                $data['version'] = get_latest_version_of_trader($data['trader']);
            }

            $createTraderOrder->handle($order, $data);

            return $this->successResponse();
        });
    }
}

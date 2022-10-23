<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\StoreOrderRequest;
use App\Models\FinancingOrder;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * @param  GetPaginatedFinancingOrder  $getPaginatedOrders
     * @return JsonResponse
     */
    public function index(GetPaginatedFinancingOrder $getPaginatedOrders): JsonResponse
    {
        $financingOrders = $getPaginatedOrders->handle();

        return fractal($financingOrders, new FinancingOrderTransformer())
            ->parseExcludes(['contract', 'power_of_attorney'])
            ->respond();
    }

    /**
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function show(FinancingOrder $order): JsonResponse
    {
        return fractal($order, new FinancingOrderTransformer())->respond();
    }

    /**
     * Handle the incoming request.
     *
     * @param  StoreOrderRequest  $request
     * @param  CreateFinancingOrder  $createFinancingOrder
     * @return JsonResponse
     */
    public function store(
        StoreOrderRequest $request,
        CreateFinancingOrder $createFinancingOrder
    ): JsonResponse {
        $status = tenant()->does_order_require_approval
            ? FinancingOrderStatus::PendingApproval
            : FinancingOrderStatus::InProgress;

        $financingOrder = $createFinancingOrder->handle(array_merge(
            $request->validated(),
            ['status' => $status]
        ));

        return fractal($financingOrder, new FinancingOrderTransformer())->respond();
    }
}

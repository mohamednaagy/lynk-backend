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
        $order->load('creator', 'approver');

        return fractal($order, new FinancingOrderTransformer())
            ->parseIncludes(['creator', 'approver'])
            ->respond();
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

        $financingOrder = $createFinancingOrder->handle(
            array_merge(
                $request->validated(),
                [
                    'status' => $status,
                    'creator_id' => auth()->user()->id,
                    'creator_type' => auth()->user()->getMorphClass(),
                    'approved_at' => $status === FinancingOrderStatus::InProgress ? now() : null,
                ]
            )
        );

        return fractal($financingOrder, new FinancingOrderTransformer())->respond();
    }
}

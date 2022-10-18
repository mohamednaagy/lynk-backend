<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Lenders\CreateFinancingOrder;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\StoreOrderRequest;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  StoreOrderRequest  $request
     * @param  CreateFinancingOrder  $createFinancingOrder
     * @return JsonResponse
     */
    public function store(StoreOrderRequest $request, CreateFinancingOrder $createFinancingOrder)
    {
        $requestData = array_merge(
            $request->validated(),
            ['status' => FinancingOrderStatus::Pending]
        );

        $financingOrder = $createFinancingOrder->handle($requestData);

        return fractal($financingOrder, new FinancingOrderTransformer())->respond();
    }
}

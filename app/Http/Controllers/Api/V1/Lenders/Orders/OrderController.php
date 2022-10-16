<?php
namespace App\Http\Controllers\Api\V1\Lenders\Orders;

use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Transformers\FinancingOrderTransformer;
use App\Actions\Contracts\Lenders\CreateFinancingOrder;
use App\Actions\Contracts\Lenders\GetPaginatedOrders;
use App\Http\Requests\V1\Lender\Orders\StoreOrderRequest;
use App\Models\FinancingOrder;

class OrderController extends Controller
{
    public function index(GetPaginatedOrders $getPaginatedOrders)
    {
        $financingOrders = $getPaginatedOrders->handle();
        return fractal($financingOrders, new FinancingOrderTransformer())
        ->parseExcludes(['contract', 'power_of_attorney'])
        ->respond();
    }

    public function show(FinancingOrder $order)
    {
       return fractal($order, new FinancingOrderTransformer())->respond();
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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

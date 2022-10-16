<?php
namespace App\Http\Controllers\Api\V1\Lenders\Orders;

use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Transformers\FinancingOrderTransformer;
use App\Actions\Contracts\Lenders\CreateFinancingOrder;
use App\Http\Requests\V1\Lender\Orders\StoreOrderRequest;

class OrderController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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

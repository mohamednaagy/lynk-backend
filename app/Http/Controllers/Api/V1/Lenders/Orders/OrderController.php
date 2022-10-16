<?php
namespace App\Http\Controllers\Api\V1\Lenders\Orders;

use App\Actions\Contracts\Lenders\CreateFinancingOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lenders\Orders\StoreOrderRequest;
use App\Transformers\FinancingOrderTransformer;

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
        $financingOrder = $createFinancingOrder->handle($request->validated());

        return fractal($financingOrder, new FinancingOrderTransformer())->respond();
    }
}

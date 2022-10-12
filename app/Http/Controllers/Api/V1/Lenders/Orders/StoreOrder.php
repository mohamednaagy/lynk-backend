<?php
namespace App\Http\Controllers\Api\V1\Lenders\Orders;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Transformers\FinancingOrderTransformer;
use App\Actions\Contracts\lender\CreateFinancingOrder;
use App\Http\Requests\Lenders\Orders\StoreOrderRequest;

class StoreOrder extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(StoreOrderRequest $request, CreateFinancingOrder $createFinancingOrder)
    {
        $financingOrder = $createFinancingOrder->handle($request->validated());

        return fractal($financingOrder, new FinancingOrderTransformer())->respond();
    }
}

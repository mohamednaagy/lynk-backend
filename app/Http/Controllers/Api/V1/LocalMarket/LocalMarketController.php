<?php

namespace App\Http\Controllers\Api\V1\LocalMarket;

use App\Actions\Contracts\LocalMarket\CreateLocalMarketOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LocalMarket\BuyLocalMarketRequest;
use App\Support\Traders\Traits\LocalMarketHelperTrait;

class LocalMarketController extends Controller
{
    use LocalMarketHelperTrait;

    public function buy(
        BuyLocalMarketRequest $request,
        CreateLocalMarketOrder $createOrder
    ) {
        $data = $request->validated();
        $order = $createOrder->handle($data);

        return response()->json($order);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Traders;

use App\Actions\Contracts\Traders\ListOrdersByTrader;
use App\Http\Controllers\Controller;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListOrders extends Controller
{
    public function __invoke(
        Request $request,
        ListOrdersByTrader $listOrdersByTrader,
        string $trader
    ): JsonResponse {
        return fractal($listOrdersByTrader->handle($trader), new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'amount',
                'selling_price',
                'status',
            ])
            ->respond();
    }
}

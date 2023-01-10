<?php

namespace App\Http\Controllers\Api\V1\Traders;

use App\Actions\Contracts\Traders\GetOrder;
use App\Http\Controllers\Controller;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowOrder extends Controller
{
    public function __invoke(
        Request $request,
        GetOrder $getOrder,
        int $order
    ): JsonResponse {
        return fractal($getOrder->handle($order), new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'amount',
                'selling_price',
                'status',
                'active_trader',
            ])
            ->respond();
    }
}

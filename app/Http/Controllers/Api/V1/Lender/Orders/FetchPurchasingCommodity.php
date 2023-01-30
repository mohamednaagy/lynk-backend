<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\GetOrder;
use App\Http\Controllers\Controller;
use App\Transformers\TraderOrderTransformer;
use Illuminate\Http\JsonResponse;

class FetchPurchasingCommodity extends Controller
{
    public function __invoke(
        GetOrder $getOrder,
        int $order
    ): JsonResponse {
        $orderDetails = $getOrder->setCompany(tenant())->setRelations(['activeTraderOrder'])->handle($order);

        return fractal($orderDetails->activeTraderOrder->first(), new TraderOrderTransformer())
            ->parseIncludes(
                'purchasing_commodity_information',
            )
            ->respond();
    }
}

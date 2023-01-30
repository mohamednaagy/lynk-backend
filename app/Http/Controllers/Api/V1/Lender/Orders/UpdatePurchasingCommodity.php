<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\GetOrder;
use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Http\Controllers\Controller;
use App\Transformers\TraderOrderTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdatePurchasingCommodity extends Controller
{
    public function __invoke(
        Request $request,
        GetOrder $getOrder,
        UpdateTraderOrder $updateTraderOrder,
        int $orderId,
        int $traderOrderId,
    ): JsonResponse {
        $orderDetails = $getOrder->setCompany(tenant())->handle($orderId);
        $traderOrderDetails = $orderDetails->activeTraderOrder()->where('id', $traderOrderId)->firstOrFail();

        return fractal($updateTraderOrder->handle($traderOrderDetails, $request->all()), new TraderOrderTransformer())
            ->parseIncludes(
                'purchasing_commodity_information',
            )
            ->respond();
    }
}

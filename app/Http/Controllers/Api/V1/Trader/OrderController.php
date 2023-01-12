<?php

namespace App\Http\Controllers\Api\V1\Trader;

use App\Actions\Contracts\Orders\GetOrder;
use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.
            perm(Area::Trader, [Subject::FinancingOrders, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware('permission:'.
            perm(Area::Trader, [Subject::FinancingOrders, Action::Show, Action::Manage])
        )->only('show');
    }

    public function index(
        GetPaginatedFinancingOrder $getPaginatedFinancingOrder
    ): JsonResponse {
        return fractal($getPaginatedFinancingOrder->setCompany(tenant())->handle(), new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'amount',
                'selling_price',
                'status',
            ])
            ->respond();
    }

    public function show(
        GetOrder $getOrder,
        int $order
    ): JsonResponse {
        return fractal($getOrder->setCompany(tenant())->handle($order), new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'amount',
                'selling_price',
                'status',
                'active_trader.id',
                'active_trader.reference',
                'active_trader.provider',
                'active_trader.status',
                'trader_order_history',
            ])
            ->respond();
    }
}

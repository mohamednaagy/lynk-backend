<?php

namespace App\Http\Controllers\Api\V1\Trader\FinancingOrders;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Actions\Contracts\Orders\GetOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Http\JsonResponse;
use Stancl\Tenancy\Database\TenantScope;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Trader, [Subject::FinancingOrders, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::Trader, [Subject::FinancingOrders, Action::Show, Action::Manage])
        )->only('show');
    }

    public function index(
        BuildFinancingOrdersQuery $buildFinancingOrdersQuery
    ): JsonResponse {
        $financingOrders = $buildFinancingOrdersQuery->setCompany(tenant())
            ->handle()
            ->paginate();

        return fractal($financingOrders, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'amount',
                'selling_price',
                'amount_formatted',
                'selling_price_formatted',
                'status',
            ])
            ->respond();
    }

    public function show(
        GetOrder $getOrder,
        int $order
    ): JsonResponse {
        $order = $getOrder->setCompany(tenant())->handle($order);

        $order->load([
            'traderOrders' => function ($query) {
                $query->where('provider', tenant()->driver)->latest('id');
            },
            'traderOrders.traderHistories',
            'traderOrders.order' => function ($query) {
                return $query->withoutGlobalScope(TenantScope::class);
            },
        ]);

        return fractal($order, (new FinancingOrderTransformer())->setArea(Area::Trader))
            ->parseIncludes([
                'id',
                'amount',
                'selling_price',
                'amount_formatted',
                'selling_price_formatted',
                'status',
                'active_trader.id',
                'active_trader.reference',
                'active_trader.provider',
                'active_trader.status',
                'trader_orders.id',
                'trader_orders.reference',
                'trader_orders.provider',
                'trader_orders.is_cancellable',
                'trader_orders.history',
                'trader_orders.products',
                'trader_orders.status',
                'trader_orders.created_at',
            ])
            ->respond();
    }
}

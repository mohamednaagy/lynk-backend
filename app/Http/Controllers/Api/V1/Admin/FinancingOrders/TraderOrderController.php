<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class TraderOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Index])
        )->only('index');

        $this->middleware(
            'permission:'.perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Show])
        )->only('show');
    }

    /**
     * @param  Company  $trader
     * @param  GetPaginatedFinancingOrder  $getPaginatedOrders
     * @return JsonResponse
     */
    public function index(Company $trader, GetPaginatedFinancingOrder $getPaginatedOrders): JsonResponse
    {
        $orders = $getPaginatedOrders->setCompany($trader)->handle();

        return fractal($orders, new FinancingOrderTransformer($trader))
            ->parseIncludes([
                'id',
                'company_id',
                'company_name',
                'status',
                'amount',
                'selling_price',
                'created_at',
            ])
            ->respond();
    }

    /**
     * @param  Company  $trader
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function show(Company $trader, FinancingOrder $order): JsonResponse
    {
        $order->load('creator');

        $orderDetails = $order->newQuery()->withWhereHas('traderOrders', function ($query) use ($trader) {
            $query->where('provider', $trader->driver);
        })->first();

        if (blank($orderDetails)) {
            throw new ModelNotFoundException();
        }

        $order->load([
            'traderOrders' => function ($query) use ($trader) {
                $query->where('provider', $trader->driver)->latest('id');
            },
            'traderOrders.traderHistories',
        ]);

        return fractal($order, new FinancingOrderTransformer($trader))
            ->parseIncludes([
                'id',
                'company_id',
                'company_name',
                'status',
                'amount',
                'selling_price',
                'can_completed',
                'created_at',
                'trader_orders.id',
                'trader_orders.reference',
                'trader_orders.provider',
                'trader_orders.is_cancellable',
                'trader_orders.history',
                'trader_orders.status',
                'trader_orders.created_at',
            ])
            ->respond();
    }
}

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
use Illuminate\Http\JsonResponse;

class LenderOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                  perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Index])
        )
            ->only('index');

        $this->middleware(
            'permission:'.
                          perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Show])
        )
            ->only('show');
    }

    /**
     * @param  Company  $lender
     * @param  GetPaginatedFinancingOrder  $getPaginatedOrders
     * @return JsonResponse
     */
    public function index(Company $lender, GetPaginatedFinancingOrder $getPaginatedOrders): JsonResponse
    {
        $orders = $getPaginatedOrders->setCompany($lender)->handle();

        return fractal($orders, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'status',
                'reference_number',
                'national_id',
                'amount',
                'selling_price',
                'status_reason',
                'creator',
                'created_at',
            ])
            ->respond();
    }

    /**
     * @param  Company  $lender
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function show(Company $lender, FinancingOrder $order): JsonResponse
    {
        $order->load('creator', 'traderOrders');

        return fractal($order, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'status',
                'reference_number',
                'national_id',
                'amount',
                'selling_price',
                'phone_country_code',
                'phone_number',
                'phone_number_formatted',
                'is_approved',
                'status_reason',
                'is_updatable',
                'creator',
                'approver',
                'trader_orders.id',
                'trader_orders.reference',
                'trader_orders.provider',
                'trader_orders.cancelable',
                'trader_orders.history',
                'trader_orders.status',
                'history',
                'creator',
                'created_at',
            ])
            ->respond();
    }
}

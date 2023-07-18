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
use Illuminate\Http\Request;

class OrderController extends Controller
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

    public function index(Request $request, GetPaginatedFinancingOrder $getPaginatedOrders): JsonResponse
    {
        $company = Company::find($request->input('company'));

        if ($company) {
            $getPaginatedOrders = $getPaginatedOrders->setCompany($company);
        }

        $orders = $getPaginatedOrders->setRelations(['company', 'creator'])->handle();

        return fractal($orders, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'status',
                'reference_number',
                'national_id',
                'amount',
                'selling_price',
                'status_reason',
                'current_step',
                'creator',
                'company_name',
                'created_at',
            ])
            ->respond();
    }

    public function show(FinancingOrder $order): JsonResponse
    {
        $order->load([
            'creator',
            'traderOrders' => function ($query) {
                $query->latest('id');
            },
            'traderOrders.traderHistories',
        ]);

        return fractal($order, (new FinancingOrderTransformer())->setArea(Area::SuperAdmin))
            ->parseIncludes([
                'id',
                'status',
                'reference_number',
                'customer_name',
                'national_id',
                'amount',
                'selling_price',
                'phone_country_code',
                'phone_number',
                'phone_number_formatted',
                'is_approved',
                'status_reason',
                'can_be_completed',
                'can_create_trader_order',
                'is_updatable',
                'is_cancellable',
                'approver',
                'trader_orders.id',
                'trader_orders.reference',
                'trader_orders.provider',
                'trader_orders.version',
                'trader_orders.failure_reason',
                'trader_orders.is_cancellable',
                'trader_orders.history',
                'trader_orders.status',
                'trader_orders.created_at',
                'creator',
                'created_at',
                'payment_proof_url',
            ])
            ->respond();
    }
}

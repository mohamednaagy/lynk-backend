<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Orders\UpdateFinancingOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\FinancingOrders\StoreOrderRequest;
use App\Http\Requests\V1\Admin\FinancingOrders\UpdateOrderRequest;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Create])
        )
            ->only('store');

        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Edit])
        )
            ->only('update');
    }

    public function index(Request $request, BuildFinancingOrdersQuery $buildFinancingOrdersQuery): JsonResponse
    {
        $orders = $buildFinancingOrdersQuery->setRelations([
            'activeTraderOrder' => fn ($query) => $query->withLastHistoryAction()->latest(),
            'company' => fn ($query) => $query->withoutGlobalScope(SoftDeletingScope::class),
            'creator',
        ])
            ->handle()
            ->paginate();

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
                'trader_orders.products',
                'trader_orders.status',
                'trader_orders.created_at',
                'creator',
                'created_at',
                'payment_proof_url',
            ])
            ->respond();
    }

    /**
     * Handle the incoming request.
     */
    public function store(
        StoreOrderRequest $request,
        CanCreateOrder $canCreateOrder,
        CreateFinancingOrder $createFinancingOrder
    ): JsonResponse {
        return DB::multipleTransaction(
            function () use (
                $request,
                $createFinancingOrder,
                $canCreateOrder
            ) {
                $company = Company::find($request->input('company_id'));
                // throw exception is balance not enough
                $canCreateOrder->handle($company);

                $status = $company->does_order_require_approval
                    ? FinancingOrderStatus::PendingApproval
                    : FinancingOrderStatus::PendingTraderOrder;

                $user = $request->user();

                $financingOrder = $createFinancingOrder->handle(
                    $company,
                    array_merge(
                        $request->validated(),
                        [
                            'status' => $status,
                            'creator_id' => $user->id,
                            'creator_type' => $user->getMorphClass(),
                            'approved_at' => $status === FinancingOrderStatus::PendingApproval ? null : now(),
                        ]
                    )
                );

                return fractal($financingOrder, new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'is_approved',
                        'status_reason',
                        'phone_country_code',
                        'phone_number',
                        'phone_number_formatted',
                    ])->respond();
            }
        );
    }

    /**
     * Summary of update
     *
     *
     * @throws AuthorizationException
     */
    public function update(
        UpdateOrderRequest $request,
        UpdateFinancingOrder $updateFinancingOrder,
        FinancingOrder $order
    ): JsonResponse {
        if ($order->status->cantBeUpdated()) {
            return $this->errorResponse(
                __('error.order_cannot_be_updated'),
                Response::HTTP_BAD_REQUEST,
                ErrorCode::ORDER_NOT_UPDATABLE
            );
        }
        $financingOrder = $updateFinancingOrder->handle($order, $request->validated());

        return fractal($financingOrder, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'status',
                'reference_number',
                'national_id',
                'amount',
                'selling_price',
                'is_approved',
                'status_reason',
                'phone_country_code',
                'phone_number',
                'phone_number_formatted',
            ])->respond();
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\BuildFinancingOrdersQuery;
use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Orders\UpdateFinancingOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Enums\TraderOrderMode;
use App\Enums\WalletType;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\ListOrderRequest;
use App\Http\Requests\V1\Lender\Orders\StoreOrderRequest;
use App\Http\Requests\V1\Lender\Orders\UpdateOrderRequest;
use App\Jobs\FinancingOrders\NotifyAdminsAboutOrderCreated;
use App\Models\FinancingOrder;
use App\Traits\HandlesFractal;
use App\Transformers\FinancingOrderTransformer;
use Cknow\Money\Money;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use HandlesFractal;

    private $sharedFields = [
        'id',
        'status',
        'reference_number',
        'customer_name',
        'national_id',
        'contract_number',
        'amount',
        'selling_price',
        'amount_formatted',
        'selling_price_formatted',
        'phone_country_code',
        'phone_number',
        'phone_number_formatted',
        'is_verification_required',
        'is_updatable',
        'is_approved',
        'is_cancellable',
        'can_be_completed',
        'can_create_trader_order',
        'payment_proof_url',
        'status_reason',
        'creator',
        'approver',
        'trader_orders.id',
        'trader_orders.provider',
        'trader_orders.mode',
        'trader_orders.reference',
        'trader_orders.failure_reason',
        'trader_orders.is_cancellable',
        'trader_orders.history',
        'trader_orders.products',
        'trader_orders.status',
        'trader_orders.cancel_details',
        'trader_orders.created_at',
        'history',
    ];

    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::FinancingOrders, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::FinancingOrders, Action::Show, Action::Manage])
        )->only('show');

        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::FinancingOrders, Action::Create, Action::Manage])
        )->only('store');

        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        )->only('update');
    }

    public function index(ListOrderRequest $request, BuildFinancingOrdersQuery $buildOrdersQuery): JsonResponse
    {
        if ($request->user()->hasRole(Role::LenderOrderCreator)) {
            $buildOrdersQuery->setCreator($request->user());
        }

        $financingOrders = $buildOrdersQuery->setCompany(tenant())
            ->setRelations([
                'activeTraderOrder' => fn ($query) => $query->withLastHistoryAction()->latest(),
            ])
            ->handle()
            ->withCount(['traderOrders as charged_trader_orders_count' => function ($query) {
                $query->whereNull('data->refunded_at');
            }])
            ->paginate();

        return fractal($financingOrders, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'status',
                'reference_number',
                'national_id',
                'amount',
                'charged_trader_orders_count',
                'selling_price',
                'amount_formatted',
                'selling_price_formatted',
                'status_reason',
                'current_step',
                'created_at',
            ])->respond();
    }
    

    /**
     * @throws AuthorizationException
     */
    public function show(Request $request, FinancingOrder $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load('creator', 'approver');

        $userRole = $request->user()->getRoleNames()->first();
        $fields = array_diff($this->sharedFields, $this->getFieldsForRole($userRole, OrderController::class, 'show'));
        return $this->formatResponse($order,  (new FinancingOrderTransformer())
        ->setArea(Area::Lender)
        ->setCurrentUser($request->user())
        , $fields);
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
                $company = tenant();
                // throw exception is balance not enough
                $canCreateOrder->handle(
                    $company,
                    Money::parseByDecimal(
                        $request->validated('amount'),
                        $company->getWallet(WalletType::CompanyWallet)->currency
                    )
                );

                $status = $company->does_order_require_approval
                    ? FinancingOrderStatus::PendingApproval
                    : ($company->trading_mode->is(TraderOrderMode::Automatic) && ! $company->require_initiate_trade_request
                        ? FinancingOrderStatus::Approved
                        : FinancingOrderStatus::PendingTraderOrder);

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

                dispatch(new NotifyAdminsAboutOrderCreated($financingOrder, $user));

                return fractal($financingOrder, new FinancingOrderTransformer())
                    ->parseIncludes([
                        'id',
                        'status',
                        'reference_number',
                        'national_id',
                        'amount',
                        'selling_price',
                        'amount_formatted',
                        'selling_price_formatted',
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
        $this->authorize('update', $order);
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
                'amount_formatted',
                'selling_price_formatted',
                'is_approved',
                'status_reason',
                'phone_country_code',
                'phone_number',
                'phone_number_formatted',
            ])->respond();
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Actions\Contracts\Orders\UpdateFinancingOrder;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\ErrorCode;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\StoreOrderRequest;
use App\Http\Requests\V1\Lender\Orders\UpdateOrderRequest;
use App\Models\FinancingOrder;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Auth\Access\AuthorizationException;
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

    /**
     * @param  Request  $request
     * @param  GetPaginatedFinancingOrder  $getPaginatedOrders
     * @return JsonResponse
     */
    public function index(Request $request, GetPaginatedFinancingOrder $getPaginatedOrders): JsonResponse
    {
        if ($request->user()->hasRole(Role::LenderOrderCreator)) {
            $getPaginatedOrders->setCreator($request->user());
        }

        $financingOrders = $getPaginatedOrders->handle(10);

        return fractal($financingOrders, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'status',
                'reference_number',
                'national_id',
                'amount',
                'selling_price',
                'status_reason',
            ])->respond();
    }

    /**
     * @param  FinancingOrder  $order
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function show(FinancingOrder $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load('creator', 'approver');

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
                'is_updatable',
                'is_approved',
                'status_reason',
                'creator',
                'approver',
                'history',
            ])->respond();
    }

    /**
     * Handle the incoming request.
     *
     * @param  StoreOrderRequest  $request
     * @param  CreateFinancingOrder  $createFinancingOrder
     * @param  DeductOrderCreationFee  $deductOrderCreationFee
     * @param  CanCreateOrder  $canCreateOrder
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function store(
        StoreOrderRequest $request,
        CreateFinancingOrder $createFinancingOrder,
        DeductOrderCreationFee $deductOrderCreationFee,
        CanCreateOrder $canCreateOrder
    ): JsonResponse {
        return DB::multipleTransaction(
            function () use ($request, $createFinancingOrder, $deductOrderCreationFee, $canCreateOrder) {
                $company = tenant();
                // throw exception is balance not enough
                $canCreateOrder->handle($company);

                $status = tenant()->does_order_require_approval
                    ? FinancingOrderStatus::PendingApproval
                    : FinancingOrderStatus::WaitingClientWakala;

                $user = $request->user();

                $financingOrder = $createFinancingOrder->handle(
                    $company,
                    array_merge(
                        $request->validated(),
                        [
                            'status' => $status,
                            'creator_id' => $user->id,
                            'creator_type' => $user->getMorphClass(),
                            'approved_at' => $status === FinancingOrderStatus::WaitingClientWakala ? now() : null,
                            'is_verification_required' => true,
                        ]
                    )
                );

                // deduct the cost from the wallet
                $deductOrderCreationFee->handle($financingOrder);

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
     * @param  UpdateOrderRequest  $request
     * @param  UpdateFinancingOrder  $updateFinancingOrder
     * @param  FinancingOrder  $order
     * @return JsonResponse
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
        $financingOrder = $updateFinancingOrder->update($order, $request->validated());

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

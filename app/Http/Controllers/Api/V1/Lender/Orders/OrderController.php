<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Actions\Contracts\Orders\UpdateFinancingOrder;
use App\Actions\Contracts\Wallets\CreateTransactions;
use App\Actions\Contracts\Wallets\DeductOrderCreationFee;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Exceptions\BalanceIsNotEnoughException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\StoreOrderRequest;
use App\Http\Requests\V1\Lender\Orders\UpdateOrderRequest;
use App\Models\FinancingOrder;
use App\Transformers\FinancingOrderTransformer;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Illuminate\Http\JsonResponse;
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
     * @param  GetPaginatedFinancingOrder  $getPaginatedOrders
     * @return JsonResponse
     */
    public function index(GetPaginatedFinancingOrder $getPaginatedOrders): JsonResponse
    {
        $financingOrders = $getPaginatedOrders->handle();

        return fractal($financingOrders, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'status',
                'company_id',
                'reference_number',
                'national_id',
                'amount',
                'selling_price',
                'is_approved',
                'status_reason',
            ])->respond();
    }

    /**
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function show(FinancingOrder $order): JsonResponse
    {
        $order->load('creator', 'approver');

        return fractal($order, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'status',
                'company_id',
                'reference_number',
                'national_id',
                'amount',
                'selling_price',
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
     * @return JsonResponse
     *
     * @throws ExceptionInterface
     */
    public function store(
        StoreOrderRequest $request,
        CreateFinancingOrder $createFinancingOrder,
        CreateTransactions $createTransactions
    ): JsonResponse {
        return DB::transaction(
            function () use ($createFinancingOrder, $createTransactions, $request) {
                $company = tenant();
                // throw exception is balance not enough
                if (! app(CanCreateOrder::class)->handle($company)) {
                    throw new BalanceIsNotEnoughException();
                }

                $status = tenant()->does_order_require_approval
                    ? FinancingOrderStatus::PendingApproval
                    : FinancingOrderStatus::WaitingClientWakala;

                $user = $request->user();

                $financingOrder = $createFinancingOrder->handle(
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
                app(DeductOrderCreationFee::class)->handle($createTransactions, $financingOrder);

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
     * @param  UpdateOrderRequest  $updateOrderRequest
     * @param  UpdateFinancingOrder  $updateFinancingOrder
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function update(
        UpdateOrderRequest $updateOrderRequest,
        UpdateFinancingOrder $updateFinancingOrder,
        FinancingOrder $order
    ): JsonResponse {
        $financingOrder = $updateFinancingOrder->update($order, $updateOrderRequest->validated());

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
            ])->respond();
    }
}

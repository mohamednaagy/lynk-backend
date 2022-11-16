<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Actions\Contracts\Orders\UpdateFinancingOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
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
        $this->middleware('permission:'.perm(Area::Lender, [Subject::FinancingOrders, Action::Index, Action::Manage]))->only('index');
        $this->middleware('permission:'.perm(Area::Lender, [Subject::FinancingOrders, Action::Show, Action::Manage]))->only('show');
        $this->middleware('permission:'.perm(Area::Lender, [Subject::FinancingOrders, Action::Create, Action::Manage]))->only('store');
        $this->middleware('permission:'.perm(Area::Lender, [Subject::FinancingOrders, Action::Edit, Action::Manage]))->only('update');
    }

    /**
     * @param  GetPaginatedFinancingOrder  $getPaginatedOrders
     * @return JsonResponse
     */
    public function index(GetPaginatedFinancingOrder $getPaginatedOrders): JsonResponse
    {
        $financingOrders = $getPaginatedOrders->handle();

        return fractal($financingOrders, new FinancingOrderTransformer())
            ->parseExcludes(['contract', 'power_of_attorney'])
            ->respond();
    }

    /**
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function show(FinancingOrder $order): JsonResponse
    {
        $order->load('creator', 'approver');

        return fractal($order, new FinancingOrderTransformer())
            ->parseIncludes(['creator', 'approver', 'history'])
            ->respond();
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
    public function store(StoreOrderRequest $request, CreateFinancingOrder $createFinancingOrder): JsonResponse
    {
        return DB::transaction(
            static function () use ($createFinancingOrder, $request) {
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

                return fractal($financingOrder, new FinancingOrderTransformer())->respond();
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

        return fractal($financingOrder, new FinancingOrderTransformer())->respond();
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Actions\Contracts\Orders\UpdateFinancingOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\StoreOrderRequest;
use App\Http\Requests\V1\Lender\Orders\UpdateOrderRequest;
use App\Mail\OrderCreated;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Transformers\FinancingOrderTransformer;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function __construct()
    {
        // __REVIEW__ break down long line to be easy to read
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

        // __REVIEW__ remove excludes
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
                // __REVIEW__ before creating order, check if enough balance exists or not
                // If balance is not enough, return custom exception called BalanceIsNotEnough that will render
                // the following
                // "message": "No engouh balance", //english
                // "message": "لا يوجد رصيد كافي" , //arabic
                // "code": "Suitable error code"
                // See example Modules/Otpify/Exceptions/OtpCodeExpiredException.php

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

                //__REVIEW__ we should deduct from the company wallet here

                $notification = 'notification model';
                $admins = User::role([Role::LenderAdmin, Role::LenderSupervisor])->get();

                Mail::cc($admins)->queue(new OrderCreated($notification));

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

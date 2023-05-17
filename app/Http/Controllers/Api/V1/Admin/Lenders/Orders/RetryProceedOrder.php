<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Actions\Contracts\Orders\RetryOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RetryProceedOrder extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @param  RetryOrder  $retryOrder
     * @param  int  $order
     * @return JsonResponse
     */
    public function __invoke(
        RetryOrder $retryOrder,
        int $order,
    ) {
        return DB::transaction(
            function () use ($retryOrder, $order) {
                $retryOrder->handle($order);

                return $this->successResponse();
            }
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders;

use App\Actions\Contracts\Orders\CompleteOrder as CompleteOrderInterface;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\CompleteOrderRequest;
use Illuminate\Support\Facades\DB;

class CompleteOrder extends Controller
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(
        CompleteOrderRequest $request,
        CompleteOrderInterface $completeOrder,
        int $order,
    ) {
        return DB::transaction(
            function () use ($request, $completeOrder, $order) {
                $completeOrder->handle($order, $request->validated());

                return $this->successResponse();
            }
        );
    }
}

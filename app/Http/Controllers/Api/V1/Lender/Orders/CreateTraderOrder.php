<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\TraderOrders\InitiateTraderOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CreateTraderOrder extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Lender, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    /**
     * Handle the incoming request.
     *
     * @return Response
     */
    public function __invoke(InitiateTraderOrder $initiateTraderOrder, int $orderId)
    {
        return DB::multipleTransaction(function () use ($initiateTraderOrder, $orderId) {
            $initiateTraderOrder->handle(request()->user(), $orderId);

            return $this->successResponse();
        });
    }
}

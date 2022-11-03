<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\GetOrdersStats as OrdersGetOrdersStats;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetOrdersStats extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  OrdersGetOrdersStats  $getOrdersStats
     * @return JsonResponse
     */
    public function __invoke(Request $request, OrdersGetOrdersStats $getOrdersStats)
    {
        if ($request->user()->hasRole(Role::LenderOrderCreator)) {
            $getOrdersStats->setCreator($request->user());
        }

        return $this->successResponse($getOrdersStats->handle());
    }
}

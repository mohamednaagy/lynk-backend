<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\GetOrdersStats as OrdersGetOrdersStats;
use App\Enums\Role;
use App\Http\Controllers\Controller;

class GetOrdersStats extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  OrdersGetOrdersStats  $getOrdersStats
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(OrdersGetOrdersStats $getOrdersStats)
    {
        if (auth()->user()->hasRole(Role::LenderOrderCreator)) {
            $getOrdersStats->setCreator(auth()->user());
        }

        return $this->successResponse($getOrdersStats->handle());
    }
}

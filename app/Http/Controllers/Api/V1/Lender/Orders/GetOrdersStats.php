<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\GetOrdersStats as OrdersGetOrdersStats;
use App\Actions\Contracts\Orders\GetOrdersStatsByCreator;
use App\Enums\Role;
use App\Http\Controllers\Controller;

class GetOrdersStats extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  OrdersGetOrdersStats  $getOrdersStats
     * @param  GetOrdersStatsByCreator  $getOrdersStatsByCreator
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(OrdersGetOrdersStats $getOrdersStats, GetOrdersStatsByCreator $getOrdersStatsByCreator)
    {
        if (auth()->user()->hasRole(Role::LenderOrderCreator)) {
            return $this->successResponse($getOrdersStatsByCreator->handle(tenant(), auth()->user()));
        }

        return $this->successResponse($getOrdersStats->handle(tenant()));
    }
}

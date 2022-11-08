<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Actions\Contracts\Orders\GetOrdersVolume as OrdersGetOrdersVolume;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Lender\Orders\OrderVolumeRequest;
use Illuminate\Http\Request;

class GetOrdersVolume extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  OrderVolumeRequest  $orderVolumeRequest
     * @param  OrdersGetOrdersVolume  $getOrdersVolume
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(OrderVolumeRequest $orderVolumeRequest, OrdersGetOrdersVolume $getOrdersVolume)
    {
        $volumes = $getOrdersVolume->handle($orderVolumeRequest->validated());

        return $this->successResponse(['y_axis' => array_values($volumes), 'x_axis' => array_keys($volumes)]);
    }
}

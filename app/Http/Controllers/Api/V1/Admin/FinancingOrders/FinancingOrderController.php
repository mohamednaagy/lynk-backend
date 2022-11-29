<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Transformers\FinancingOrderTransformer;

class FinancingOrderController extends Controller
{
    public function index(Company $company)
    {
        $orders = $company->orders()->with('creator')->paginate();

        return fractal($orders, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'status',
                'reference_number',
                'national_id',
                'amount',
                'selling_price',
                'status_reason',
                'creator',
                'created_at',
            ])
            ->respond();
    }

    /**
     * @param  FinancingOrder  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Company $company, FinancingOrder $order)
    {
        $order->load('creator');

        return fractal($order, new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'status',
                'reference_number',
                'national_id',
                'amount',
                'selling_price',
                'phone_country_code',
                'phone_number',
                'phone_number_formatted',
                'is_approved',
                'status_reason',
                'creator',
                'approver',
                'history',
                'creator',
                'created_at',
            ])
            ->respond();
    }
}

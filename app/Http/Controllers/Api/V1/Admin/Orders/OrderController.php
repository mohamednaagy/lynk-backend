<?php

namespace App\Http\Controllers\Api\V1\Admin\Orders;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Transformers\FinancingOrderTransformer;

class OrderController extends Controller
{
    public function index(Company $company)
    {
        return fractal($company->orders()->with('creator')->paginate(), new FinancingOrderTransformer())
            ->parseIncludes(['creator', 'created_at'])
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
            ->parseIncludes(['creator', 'created_at'])
            ->respond();
    }
}

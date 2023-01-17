<?php

namespace App\Http\Controllers\Api\V1\Admin\FinancingOrders;

use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Transformers\FinancingOrderTransformer;

class FinancingOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
                  perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Index])
        )
            ->only('index');

        $this->middleware(
            'permission:'.
                          perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Show])
        )
            ->only('show');
    }

    public function index(Company $company, GetPaginatedFinancingOrder $getPaginatedOrders)
    {
        $orders = $getPaginatedOrders->setCompany($company)->handle();

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
     * @param  Company  $company
     * @param  FinancingOrder  $order
     * @return JsonResponse
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
                'is_updatable',
                'creator',
                'approver',
                'history',
                'creator',
                'created_at',
            ])
            ->respond();
    }
}

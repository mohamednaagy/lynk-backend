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
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TraderOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Index])
        )->only('index');

        $this->middleware(
            'permission:'.perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Manage, Action::Show])
        )->only('show');
    }

    /**
     * @param  Company  $trader
     * @param  GetPaginatedFinancingOrder  $getPaginatedOrders
     * @return JsonResponse
     */
    public function index(Company $trader, GetPaginatedFinancingOrder $getPaginatedOrders): JsonResponse
    {
        $orders = $getPaginatedOrders->setCompany($trader)->handle();

        return fractal($orders, new FinancingOrderTransformer($trader))
            ->parseIncludes([
                'id',
                'company_id',
                'company_name',
                'status',
                'amount',
                'selling_price',
                'created_at',
            ])
            ->respond();
    }

    /**
     * @param  Company  $trader
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function show(Company $trader, FinancingOrder $order): JsonResponse
    {
        $order->load('creator');

        $orderDetails = $order->newQuery()->withWhereHas('traderOrder', function ($query) use ($trader) {
            $query->where('provider', $trader->driver);
        })->where('company_id', $trader->id);

        if (blank($orderDetails)) {
            throw new NotFoundHttpException();
        }

        return fractal($order, new FinancingOrderTransformer($trader))
            ->parseIncludes([
                'id',
                'company_id',
                'company_name',
                'status',
                'amount',
                'selling_price',
                'created_at',
            ])
            ->respond();
    }
}

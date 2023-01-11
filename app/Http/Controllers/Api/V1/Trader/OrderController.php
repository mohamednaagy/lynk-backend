<?php

namespace App\Http\Controllers\Api\V1\Trader;

use App\Actions\Contracts\Orders\GetOrder;
use App\Actions\Contracts\Orders\GetPaginatedFinancingOrder;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Transformers\FinancingOrderTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.
            perm(Area::Trader, [Subject::FinancingOrders, Action::Index, Action::Manage])
        )->only('index');

        $this->middleware('permission:'.
            perm(Area::Trader, [Subject::FinancingOrders, Action::Show, Action::Manage])
        )->only('show');
    }

    public function index(
        Request $request,
        GetPaginatedFinancingOrder $getPaginatedFinancingOrder
    ): JsonResponse {
        return fractal($getPaginatedFinancingOrder->setTrader($request->get('trader'))->handle(), new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'amount',
                'selling_price',
                'status',
            ])
            ->respond();
    }

    public function show(
        GetOrder $getOrder,
        int $order
    ): JsonResponse {
        return fractal($getOrder->handle($order), new FinancingOrderTransformer())
            ->parseIncludes([
                'id',
                'amount',
                'selling_price',
                'status',
                'active_trader',
            ])
            ->respond();
    }
}

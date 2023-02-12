<?php

namespace App\Http\Controllers\Api\V1\Trader\TraderOrders;

use App\Actions\Contracts\Orders\GetOrder;
use App\Actions\Contracts\Orders\TraderOrders\PurchasingCommodity\HandlePurchasingCommodity;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\FinancingOrderStatus;
use App\Enums\Subject;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Orders\UpdatePurchasingCommodityRequest;
use App\Models\TraderOrder;
use App\Support\Traders\TraderHelperTrait;
use App\Transformers\TraderOrderTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdatePurchasingCommodity extends Controller
{
    use TraderHelperTrait;

    public function __construct()
    {
        $this->middleware(
            'permission:'.
                perm(Area::Trader, [Subject::FinancingOrders, Action::Show, Action::Manage])
        );
    }

    public function __invoke(
        UpdatePurchasingCommodityRequest $request,
        GetOrder $getOrder,
        int $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($getOrder, $order, $traderOrder, $request) {
            $financingOrder = $getOrder->setCompany(tenant())->handle($order);

            if ($financingOrder->status->cantMoveTo(FinancingOrderStatus::CommodityPurchased)) {
                throw new OrderStatusDoesNotFollowSequenceException();
            }

            app(HandlePurchasingCommodity::class)->handle($request, $financingOrder, $traderOrder);

            return fractal($traderOrder, new TraderOrderTransformer())
                ->parseIncludes(
                    'purchasing_commodity_information',
                )
                ->respond();
        });
    }
}

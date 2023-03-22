<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Actions\Contracts\Orders\GetOrderAndTraderOrderLockedForUpdate;
use App\Actions\Contracts\Orders\TraderOrders\PurchasingCommodity\HandlePurchasingCommodity;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\MurabhaStep;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders\UpdatePurchasingCommodityRequest;
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
                perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Edit, Action::Manage])
        );
    }

    public function __invoke(
        UpdatePurchasingCommodityRequest $request,
        int $order,
        int $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($traderOrder, $request) {
            [$financingOrder, $traderOrder] = app(GetOrderAndTraderOrderLockedForUpdate::class)->handle($traderOrder);

            $traderOrder->ensureCanAccessStep(MurabhaStep::TraderOrderCreated);

            app(HandlePurchasingCommodity::class)->handle($request, $financingOrder, $traderOrder);

            return fractal($traderOrder, new TraderOrderTransformer())
                ->parseIncludes(
                    'purchasing_commodity_information',
                )
                ->respond();
        });
    }
}

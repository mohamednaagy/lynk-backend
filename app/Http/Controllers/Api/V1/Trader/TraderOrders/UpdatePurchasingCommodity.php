<?php

namespace App\Http\Controllers\Api\V1\Trader\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\PurchasingCommodity\HandlePurchasingCommodity;
use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Trader\Orders\UpdatePurchasingCommodityRequest;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\TraderHelperTrait;
use App\Transformers\TraderOrderTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\TenantScope;

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
        int $order,
        TraderOrder $traderOrder
    ): JsonResponse {
        return DB::transaction(function () use ($order, $traderOrder, $request) {
            $financingOrder = FinancingOrder::withoutGlobalScope(TenantScope::class)
                ->lockForUpdate()
                ->findOrFail($order);

            app(HandlePurchasingCommodity::class)->handle($request, $financingOrder, $traderOrder);

            return fractal($traderOrder, new TraderOrderTransformer())
                ->parseIncludes(
                    'purchasing_commodity_information',
                )
                ->respond();
        });
    }
}

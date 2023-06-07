<?php

namespace App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Transformers\TraderOrderTransformer;
use Illuminate\Http\JsonResponse;

class GetPurchasingCommodity extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:'.
            perm(Area::SuperAdmin, [Subject::FinancingOrders, Action::Show, Action::Manage])
        );
    }

    public function __invoke(
        Company $lender,
        FinancingOrder $order,
        TraderOrder $trader_order
    ): JsonResponse {
        return fractal($trader_order, (new TraderOrderTransformer())->setArea(Area::SuperAdmin))
            ->parseIncludes(
                'purchasing_commodity_information',
            )
            ->respond();
    }
}

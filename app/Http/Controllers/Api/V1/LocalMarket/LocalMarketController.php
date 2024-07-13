<?php

namespace App\Http\Controllers\Api\V1\LocalMarket;

use App\Actions\LocalMarket\PurchaseProductAction;
use App\Http\Controllers\Controller;
use App\Models\TraderOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalMarketController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function initOrder(
        Request $request
    ) {
        $traderOrder = TraderOrder::latest()->first();
        $financingOrder = $traderOrder->order;

        return app(PurchaseProductAction::class)->handle($traderOrder, $financingOrder, $request->company_id, $request->preferred_types, $request->amount, $request->rotation);
    }
}

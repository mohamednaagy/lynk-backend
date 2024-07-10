<?php

namespace App\Http\Controllers\Api\V1\LocalMarket;

use App\Actions\LocalMarket\PurchaseProductAction;
use App\Http\Controllers\Controller;
use App\Models\FinancingOrder;
use App\Services\LocalMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalMarketController extends Controller
{


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function initOrder(
        Request $request
    ){
        return app(PurchaseProductAction::class)->handle(FinancingOrder::find($request->financing_order), $request->company_id, $request->preferred_types, $request->amount, $request->rotation);
    }
}

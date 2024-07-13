<?php

namespace App\Http\Controllers\Api\V1\LocalMarket;

use App\Actions\LocalMarket\GetSuitableCommoditiesStocks;
use App\Actions\LocalMarket\PurchaseProductAction;
use App\Http\Controllers\Controller;
use App\Models\FinancingOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalMarketController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function getSuitableLoanStock(
        Request $request
    ) {
        $getSuitableStocks = resolve(GetSuitableCommoditiesStocks::class);
        $result = $getSuitableStocks->handle(2, 300);

        return response()->json($result);
        // return app(PurchaseProductAction::class)->handle(FinancingOrder::find($request->financing_order), $request->company_id, $request->preferred_types, $request->amount, $request->rotation);
    }
}

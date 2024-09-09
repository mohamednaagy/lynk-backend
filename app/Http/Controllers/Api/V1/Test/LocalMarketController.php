<?php

namespace App\Http\Controllers\Api\V1\Test;

use App\Actions\LocalMarket\GetSuitableCommoditiesStocks;
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
    public function getSuitableLoanStock(
        Request $request
    ) {
        $getSuitableStocks = resolve(GetSuitableCommoditiesStocks::class);

        $companyId = $request->get('company_id');
        $loanAmount = $request->get('loan_amount');
        $preferredTypes = empty($request->get('preferred_types')) ? [] : explode(',', $request->get('preferred_types'));
        $suitableStocks = $getSuitableStocks->handle($companyId, $loanAmount, $preferredTypes);

        return response()->json($suitableStocks);
    }

    /**
     * @return JsonResponse
     */
    public function buy(
        Request $request
    ) {
        $getSuitableStocks = resolve(GetSuitableCommoditiesStocks::class);

        $companyId = $request->get('company_id');
        $loanAmount = $request->get('loan_amount');
        $preferredTypes = empty($request->get('preferred_types')) ? [] : explode(',', $request->get('preferred_types'));

        $suitableStocks = $getSuitableStocks->handle($companyId, $loanAmount, $preferredTypes);

        if ($suitableStocks['isLoanCovered']) {
            $purchaseProduct = resolve(PurchaseProductAction::class);
            $purchaseProduct->handle(TraderOrder::first(), $suitableStocks['inventories']);
        } else {
            return response()->json($suitableStocks);
        }
    }
}

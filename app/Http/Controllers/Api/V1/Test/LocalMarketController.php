<?php

namespace App\Http\Controllers\Api\V1\LocalMarket;

use App\Actions\LocalMarket\GetSuitableCommoditiesStocks;
use App\Http\Controllers\Controller;
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

        return response()->json($getSuitableStocks->handle($companyId, $loanAmount, $preferredTypes));
    }
}

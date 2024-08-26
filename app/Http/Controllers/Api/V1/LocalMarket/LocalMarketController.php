<?php

namespace App\Http\Controllers\Api\V1\LocalMarket;

use App\Actions\Contracts\LocalMarket\CreateLocalMarketOrder;
use App\Actions\LocalMarket\GetSuitableCommoditiesStocks;
use App\Actions\LocalMarket\PurchaseProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LocalMarket\BuyLocalMarketRequest;
use App\Http\Requests\V1\LocalMarket\CreateOrderLocalMarketRequest;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LocalMarketController extends Controller
{
    use LocalMarketHelperTrait;

    /**
     * @return JsonResponse
     */
    public function createOrder(
        CreateOrderLocalMarketRequest $request,
        CreateLocalMarketOrder $createOrder
    ) {

        return DB::multipleTransaction(function () use ($request, $createOrder) {
            $data = $request->validated();
            $order = $createOrder->handle($data);

            return $this->successResponse();
        });
    }

    public function buy(BuyLocalMarketRequest $request)
    {

        $companyId = $request->get('company_id');
        $loanAmount = $request->get('loan_amount');
        $preferredTypes = empty($request->get('preferred_types')) ? [] : explode(',', $request->get('preferred_types'));
        $getSuitableStocks = resolve(GetSuitableCommoditiesStocks::class);
        $suitableStocks = $getSuitableStocks->handle($companyId, $loanAmount, $preferredTypes);
        if ($suitableStocks['isLoanCovered']) {
            $purchaseProduct = resolve(PurchaseProductAction::class);
            $purchaseProduct->handle($suitableStocks['inventories']);
        } else {
            return response()->json($suitableStocks);
        }
    }
}

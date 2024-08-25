<?php

use App\Enums\LocalMarketOrderStatus;
use App\Http\Controllers\Api\V1\Test\LocalMarketController;
use App\Jobs\LocalMarket\states\EligibleCommoditiesFoundStatus;
use App\Jobs\LocalMarket\states\PendingEligibleCommoditiesStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Support\Facades\Route;

Route::prefix('test')->group(function () {

    Route::get('suitable-stocks', [LocalMarketController::class, 'getSuitableLoanStock']);
    Route::post('buy', [LocalMarketController::class, 'buy']);
    Route::get('process-initiate-orders', function () {

        $localMarketOrder = LocalMarketOrder::query()
            ->where('status', LocalMarketOrderStatus::EligibleCommoditiesAvailable)->first();

        // double check if we can handle this order or not
        $eligibleCommodities = $localMarketOrder->data;

        if ($eligibleCommodities['isLoanCovered']) {
            $loanService = new LoanService;
            $loanService->buyCommodities($localMarketOrder, $localMarketOrder->company_id, $eligibleCommodities);
        } else {
            dd('there is an error ');
        }
        // dispatch(new PendingEligibleCommoditiesStatus($localMarketOrder));
        // dispatch(new EligibleCommoditiesFoundStatus($localMarketOrder));
    });
});

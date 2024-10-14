<?php

use App\Actions\LocalMarket\BuyCommoditiesAction;
use App\Actions\LocalMarket\FindEligibleCommoditiesAction;
use App\Http\Controllers\Api\V1\Test\LocalMarketController;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/test')->group(function () {

    // Route::get('suitable-stocks', [LocalMarketController::class, 'getSuitableLoanStock']);
    Route::post('buy', [LocalMarketController::class, 'buy']);
    Route::get('suitable-stocks', function () {
        $company_id = request()->input('company_id');
        $preferred_types = explode(',', request()->input('preferred_types'));
        $loan_amount = request()->input('loan_amount');
        $order_no = request()->input('order_no');

        $loanService = new LoanService;
        dd($loanService->getCommoditiesForLoan($order_no, $company_id, $loan_amount, $preferred_types));

        // (new FindEligibleCommoditiesAction(new LoanService))->handle($localMarketOrder);
        //         (new BuyCommoditiesAction(new LoanService))->handle($localMarketOrder);

        if ($localMarketOrder) {
            $localMarketOrder->status = $status;
            $localMarketOrder->save();
        }
    });

    Route::get('process-initiate-orders', function () {
        $orderId = request()->input('order_id');
        $status = request()->input('status');

        $localMarketOrder = LocalMarketOrder::query()
            ->latest()
            ->first();

        (new FindEligibleCommoditiesAction(new LoanService))->handle($localMarketOrder);
        //         (new BuyCommoditiesAction(new LoanService))->handle($localMarketOrder);

        if ($localMarketOrder) {
            $localMarketOrder->status = $status;
            $localMarketOrder->save();
        }
    });
});

<?php

use App\Actions\LocalMarket\BuyCommoditiesAction;
use App\Actions\LocalMarket\CreateLocalMarketOrderAction;
use App\Actions\LocalMarket\FindEligibleCommoditiesAction;
use App\Http\Controllers\Api\V1\Test\LocalMarketController;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/test')->group(function () {

    // Route::get('suitable-stocks', [LocalMarketController::class, 'getSuitableLoanStock']);
    Route::post('buy', [LocalMarketController::class, 'buy']);
    Route::get('suitable-stocks', function () {
        $company_id = request()->input('company_id');
        $preferred_types = [1];
        $loan_amount = request()->input('loan_amount');
        $order_no = request()->input('order_no');

        $localMarketOrder = LocalMarketOrder::find(9);
        (new BuyCommoditiesAction(new LoanService))->handle($localMarketOrder);
        dd('dwdw');

        // $loanService = new LoanService;
        // dd($loanService->getCommoditiesForLoan($order_no, $company_id, $loan_amount, $preferred_types));

        $data['currency'] = 'SAR';
        $data['national_id'] = 312343432432;
        $data['trader_order_id'] = 22;
        $data['amount'] = $loan_amount;
        $data['customer_name'] = 'Mohamed NAser';
        $data['external_order_no'] = $order_no;
        $data['source'] = 1;
        $data['company_id'] = $company_id;
        $data['buying_uuid'] = 'uuid_112';
        $data['preferred_commodity_type'] = $preferred_types;
        Log::channel('local_market')->info('data prepare before saved for  ', $data);

        return app(CreateLocalMarketOrderAction::class)->handle($data);

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

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
        $preferred_types = explode(',', request()->input('preferred_types'));
        $loan_amount = request()->input('loan_amount');
        $order_no = request()->input('order_no');

        $loanService = new LoanService;

        $data['currency'] = 'SAR';
        $data['national_id'] = 312343432432;
        $data['trader_order_id'] = 22;
        $data['amount'] = 12000;
        $data['customer_name'] = 'Mohamed NAser';
        $data['external_order_no'] = 'od_1232';
        $data['source'] = 1;
        $data['company_id'] = 2;
        $data['buying_uuid'] = 'uuid_112';
        $data['preferred_commodity_type'] = [1, 2, 3];
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

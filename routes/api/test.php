<?php

use App\Actions\LocalMarket\BuyCommoditiesAction;
use App\Actions\LocalMarket\FindEligibleCommoditiesAction;
use App\Http\Controllers\Api\V1\Test\LocalMarketController;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use App\Services\LocalMarket\OrderService;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/test')->group(function () {

    Route::get('suitable-stocks', [LocalMarketController::class, 'getSuitableLoanStock']);
    Route::post('buy', [LocalMarketController::class, 'buy']);
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

Route::prefix('v1/test')->group(function () {

    Route::get('cancel_test', function (\Illuminate\Http\Request $request) {

        if ($request->has('order_id')) {
            $order = LocalMarketOrder::where('id', $request->order_id)->first();
        } else {
            $order = LocalMarketOrder::latest()->first();
        }
        (new OrderService)->cancelOrder($order);
    });
    Route::get('suitable-stocks', [LocalMarketController::class, 'getSuitableLoanStock']);
    Route::post('buy', [LocalMarketController::class, 'buy']);
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

<?php

use App\Actions\LocalMarket\BuyCommoditiesAction;
use App\Actions\LocalMarket\FindEligibleCommoditiesAction;
use App\Enums\TraderOrderCancelReason;
use App\Http\Controllers\Api\V1\Test\LocalMarketController;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use App\Services\LocalMarket\OrderService;
use App\Support\Traders\Facades\Trader as FacadesTrader;
use Illuminate\Support\Facades\Log;
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

        $traderOrder = \App\Models\TraderOrder::where('status', \App\Enums\TraderOrderStatus::InProgress)->latest()->first();
        //        dd($traderOrder->order->id);
        FacadesTrader::driver($traderOrder->provider, $traderOrder->version)
            ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::ExpiredContractSignTime);

        return $traderOrder->order->id;
        //        Log::info("Cancelled Trader Order ID: {$traderOrder->id} due to timeout.");
        //        if ($request->has('order_id')) {
        //            $order = LocalMarketOrder::where('id', $request->order_id)->first();
        //        } else {
        //            $order = LocalMarketOrder::latest()->first();
        //        }
        //        (new OrderService)->changeOrderStatus($order , \App\Enums\LocalMarketOrderStatus::PendingSellCommodities);
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

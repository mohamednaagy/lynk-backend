<?php

use App\Http\Controllers\Api\V1\Test\LocalMarketController;
use App\Models\LocalMarketOrder;
use Illuminate\Support\Facades\Route;

Route::prefix('test')->group(function () {

    Route::get('suitable-stocks', [LocalMarketController::class, 'getSuitableLoanStock']);
    Route::post('buy', [LocalMarketController::class, 'buy']);
    Route::get('process-initiate-orders', function () {
        $orderId = request()->input('order_id');
        $status = request()->input('status');

        $localMarketOrder = LocalMarketOrder::query()
            ->where('id', $orderId)
            ->first();

        if ($localMarketOrder) {
            $localMarketOrder->status = $status;

            $localMarketOrder->save();
        }
    });
});

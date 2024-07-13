<?php

use App\Http\Controllers\Api\V1\Test\LocalMarketController;
use Illuminate\Support\Facades\Route;

Route::prefix('test')->group(function () {

    Route::get('suitable-stocks', [LocalMarketController::class, 'getSuitableLoanStock']);
    Route::post('buy', [LocalMarketController::class, 'buy']);
});

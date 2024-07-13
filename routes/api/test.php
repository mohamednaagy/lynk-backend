<?php

use App\Http\Controllers\Api\V1\LocalMarket\LocalMarketController;
use Illuminate\Support\Facades\Route;

Route::prefix('test')->group(function () {

    Route::get('suitable-stocks', [LocalMarketController::class, 'getSuitableLoanStock'])->name('loanStocks.getSuitable');
});

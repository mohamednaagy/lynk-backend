<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LocalMarket\LocalMarketController;
Route::prefix('v1/local-market')->name('api.v1.admins.')->group(function () {
    Route::post('/initiate-order', [LocalMarketController::class, 'initOrder'])->name('localMarket.initiate-order');
});

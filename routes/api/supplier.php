<?php

use App\Enums\Role;
use App\Http\Controllers\Api\V1\Supplier\Auth\CompleteRegister;
use App\Http\Controllers\Api\V1\Supplier\CommodityItem\CommodityItemController;
use App\Http\Controllers\Api\V1\Supplier\CommodityType\CommodityTypeController;
use App\Http\Controllers\Api\V1\Supplier\Constant\ConstantController;
use App\Http\Controllers\Api\V1\Supplier\Location\SupplierLocation;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
use App\Http\Controllers\Api\V1\Supplier\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Supplier\Inventory\LocalMarketInventoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1/supplier')->name('api.v1.supplier.')->group(function () {
    Route::middleware([
        'auth:sanctum',
        'role:'.implode('|', [
            Role::SupplierAdmin,
            Role::SupplierApiAdmin,
        ]),
        InitializeTenancyByRequestData::class,
    ])->group(function () {
        Route::get('auth', GetAuthUser::class);
        Route::get('constants', [ConstantController::class, 'index']);
        Route::get('commodity-types', [CommodityTypeController::class, 'index']);
        Route::apiResource('locations', SupplierLocation::class)->middleware('checkDataOfSupplier');
        Route::apiResource('commodity-items', CommodityItemController::class)->middleware('checkDataOfSupplier');
        Route::apiResource('commodity-items/{item}/inventory', LocalMarketInventoryController::class)->middleware('checkDataOfSupplier');

    });
    Route::post('{user}/sign-up', CompleteRegister::class)->name('sign-up');

});

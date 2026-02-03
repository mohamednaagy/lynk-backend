<?php

use App\Enums\Area;
use App\Enums\Role;
use App\Http\Controllers\Api\V1\Supplier\Auth\CompleteRegister;
use App\Http\Controllers\Api\V1\Supplier\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Supplier\CommodityItem\CommodityItemController;
use App\Http\Controllers\Api\V1\Supplier\CommodityType\CommodityTypeController;
use App\Http\Controllers\Api\V1\Supplier\CommodityType\CommodityTypesLiteList;
use App\Http\Controllers\Api\V1\Supplier\Constant\ConstantController;
use App\Http\Controllers\Api\V1\Supplier\Inventory\LocalMarketInventoryController;
use App\Http\Controllers\Api\V1\Supplier\Location\CommodityLocationLiteList;
use App\Http\Controllers\Api\V1\Supplier\Location\SupplierLocation;
use App\Http\Controllers\Api\V1\Supplier\Users\ResendInvitationToUserController;
use App\Http\Controllers\Api\V1\Supplier\Users\UsersController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

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
        'auth:api',
        'role:'.implode('|', [
            Role::SupplierAdmin,
            Role::SupplierApiAdmin,
        ]),
        InitializeTenancyByRequestData::class,
    ])->group(function () {
        Route::get('auth', GetAuthUser::class);
        Route::group(['middleware' => 'verified.email:'.Area::CommoditySupplier], function () {
            Route::get('constants', [ConstantController::class, 'index']);
            Route::prefix('commodity-types')->group(function () {
                Route::get('/', [CommodityTypeController::class, 'index']);
                Route::get('/dropdown-list', CommodityTypesLiteList::class);
            });
            Route::get('locations/dropdown-list', CommodityLocationLiteList::class);
            Route::apiResource('locations', SupplierLocation::class)->middleware('checkDataOfSupplier');
            Route::apiResource('commodity-items', CommodityItemController::class)->middleware('checkDataOfSupplier');
            Route::apiResource('commodity-items/{item}/inventory', LocalMarketInventoryController::class)->middleware('checkDataOfSupplier');
            Route::prefix('users')->group(function () {
                Route::get('/', [UsersController::class, 'index']);
                Route::post('/', [UsersController::class, 'store']);
                Route::put('{user}', [UsersController::class, 'update']);
                Route::get('{user}', [UsersController::class, 'show']);
                Route::delete('{user}', [UsersController::class, 'destroy']);
                Route::post('{user}/resend-invitation', [ResendInvitationToUserController::class, '__invoke']);
            })->middleware('checkDataOfSupplier');
        });

    });
    Route::post('{user}/sign-up', CompleteRegister::class)->name('sign-up');

});

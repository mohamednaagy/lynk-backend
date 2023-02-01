<?php

use App\Enums\Role;
use App\Http\Controllers\Api\V1\Trader\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Trader\Auth\UpdateMyProfile;
use App\Http\Controllers\Api\V1\Trader\FinancingOrders\OrderController;
use App\Http\Controllers\Api\V1\Trader\FinancingOrders\TraderOrders\MurabhaCompleteDocument\GetMurabhaCompleteDocument;
use App\Http\Controllers\Api\V1\Trader\FinancingOrders\TraderOrders\MurabhaCompleteDocument\UpdateMurabhaCompleteDocument;
use App\Http\Controllers\Api\V1\Trader\Users\UserController;
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

Route::prefix('v1/trader')->name('api.v1.')->group(function () {
    Route::middleware([
        InitializeTenancyByRequestData::class,
        'auth:sanctum',
        'role:'.implode('|', [
            Role::TraderAdmin,
        ]),
        InitializeTenancyByRequestData::class,
    ])->group(function () {
        Route::get('auth', GetAuthUser::class);

        Route::middleware('checkCompanyStatus')->group(function () {
            Route::put('auth/profile', UpdateMyProfile::class);

            Route::prefix('/orders/{order}')->group(function () {
                Route::prefix('/trader-orders/{trader_order}')->group(function () {
                    Route::get('/murabha-complete', GetMurabhaCompleteDocument::class);
                    Route::post('/murabha-complete', UpdateMurabhaCompleteDocument::class);
                });
            });

            Route::apiResource('users', UserController::class);
            Route::apiResource('orders', OrderController::class)->only(['index', 'show']);
        });
    });
});

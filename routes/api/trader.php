<?php

use App\Enums\Role;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Trader\OrderController;
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
        'auth:sanctum',
        'role:' . implode('|', [
            Role::TraderAdmin
        ]),
        InitializeTenancyByRequestData::class,
    ])->group(function () {
        Route::apiResource('orders', OrderController::class)->only(['index', 'show']);
    });
});

<?php

use App\Enums\Role;
use App\Http\Controllers\Api\V1\Lender\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Lender\Auth\RegisterController;
use App\Http\Controllers\Api\v1\Lender\Users\UserController;
use App\Http\Controllers\Api\V1\Lenders\Orders\OrderController;
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

Route::prefix('v1/lender')->group(function () {
    Route::post('/register', RegisterController::class);

    Route::middleware([
        'auth:sanctum',
        'role:'.Role::LenderAdmin,
        InitializeTenancyByRequestData::class,
    ])->group(function () {
        Route::get('auth', GetAuthUser::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('orders', OrderController::class);
    });
});

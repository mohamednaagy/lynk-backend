<?php

use App\Enums\Role;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Lender\Auth\GetAuthUser;
use App\Http\Controllers\Api\v1\Lender\Users\UserController;
use App\Http\Controllers\Api\V1\Lender\Auth\CompleteRegister;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
use App\Http\Controllers\Api\V1\Lender\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Lenders\Orders\OrderController;

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

Route::prefix('v1/lender')->name('api.v1.')->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('{user}/complete-register', CompleteRegister::class)->name('lender.complete-register');

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

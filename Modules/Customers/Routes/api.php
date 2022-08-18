<?php

namespace Modules\Permission\Enums;

use Illuminate\Support\Facades\Route;
use Modules\Customers\Http\Controllers\Api\GetAuthUser;
use Modules\Customers\Http\Controllers\Api\V1\Admin\CustomerController;
use Modules\Customers\Http\Controllers\Api\V1\Auth\RegisterController;

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

Route::post('/register', RegisterController::class);

Route::middleware(['auth:api', 'role:' . Role::Customer])->prefix('customer')->group(function () {
    Route::get('/auth', GetAuthUser::class);

});

Route::middleware(['auth:api', 'role:' . Role::Admin])->prefix('v1/admin')->group(function () {
//Route::prefix('v1/admin')->group(function () {
    Route::apiResource('customers', CustomerController::class)->parameters(['customers' => 'id']);
});

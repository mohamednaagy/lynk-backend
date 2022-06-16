<?php

namespace Modules\Permission\Enums;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Customers\Http\Controllers\Api\GetAuthUser;
use Modules\Customers\Http\Controllers\Api\Auth\RegisterController;

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

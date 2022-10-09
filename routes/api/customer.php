<?php

use App\Enums\Area;
use App\Enums\Role;
use App\Http\Controllers\Api\V1\Customers\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Customers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

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

Route::prefix('v1/customer')->group(function () {
    Route::post('/register', RegisterController::class);

    Route::middleware(['auth:sanctum', 'role:'.Role::Customer])->group(function () {
        Route::get('auth', GetAuthUser::class);

        Route::middleware(['checkAreaOtp:'.Area::Customer])->group(function () {
            // add the customer apis here that requires OTP verification before accessing
        });
    });
});

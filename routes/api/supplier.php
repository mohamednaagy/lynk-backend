<?php

use App\Enums\Role;
// use App\Http\Controllers\Api\V1\Trader\Auth\CompleteRegister;
// use App\Http\Controllers\Api\V1\Trader\Auth\GetAuthUser;
// use App\Http\Controllers\Api\V1\Trader\Auth\ResendInvitationToUser;
// use App\Http\Controllers\Api\V1\Trader\Auth\UpdateMyProfile;

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

Route::prefix('v1/commodity-suppliers')->name('api.v1.supplier.')->group(function () {
    Route::middleware([
        InitializeTenancyByRequestData::class,
        'auth:sanctum',
        'role:'.implode('|', [
            Role::SupplierAdmin,
        ]),
    ])->group(function () {
        //Route::get('auth', GetAuthUser::class);

            //Route::put('auth/profile', UpdateMyProfile::class);

           // Route::post('users/{user}/resend-invitation', ResendInvitationToUser::class);
            Route::apiResource('users', UserController::class);
    });

});

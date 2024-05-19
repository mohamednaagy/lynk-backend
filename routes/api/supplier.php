<?php

use App\Enums\Role;
use App\Http\Controllers\Api\V1\Supplier\Auth\CompleteRegister;
// use App\Http\Controllers\Api\V1\Trader\Auth\CompleteRegister;
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


Route::prefix('v1/commodity-suppliers')->name('api.v1.commodity-supplier.')->group(function () {
    Route::middleware([
        'auth:sanctum',
        'role:'.implode('|', [
            Role::SupplierAdmin,
            Role::SupplierApiAdmin,

        ]),
    ])->group(function () {
      
        Route::get('constants', [App\Http\Controllers\Api\V1\Supplier\Constant\ConstantController::class, 'index']);

    });
    Route::post('{user}/complete-register', CompleteRegister::class)->name('sign-up');

});

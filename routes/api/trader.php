<?php

use App\Enums\Area;
use App\Enums\Role;
use App\Http\Controllers\Api\V1\Trader\Auth\CompleteRegister;
use App\Http\Controllers\Api\V1\Trader\Auth\ResendInvitation;
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
        'auth:sanctum',
        'role:'.implode('|', [
            Role::TraderAdmin,
        ]),
        InitializeTenancyByRequestData::class,
    ])->group(
        function () {
            Route::middleware('verified.email:'.Area::Trader)->group(function () {
                Route::middleware('checkCompanyStatus')->group(function () {
                    Route::post('users/{user}/resend-invitation', ResendInvitation::class);
                });
            });
        });
    Route::post('{user}/complete-register', CompleteRegister::class)->name('trader.complete-register');
});

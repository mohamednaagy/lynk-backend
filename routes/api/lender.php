<?php

use App\Enums\Area;
use App\Enums\Role;
use App\Http\Controllers\Api\V1\Lender\Auth\CompleteRegister;
use App\Http\Controllers\Api\V1\Lender\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Lender\Auth\Register;
use App\Http\Controllers\Api\V1\Lender\Auth\ResendInvitation;
use App\Http\Controllers\Api\V1\Lender\Orders\ApproveOrder;
use App\Http\Controllers\Api\V1\Lender\Orders\CancelOrder;
use App\Http\Controllers\Api\V1\Lender\Orders\GetOrdersStats;
use App\Http\Controllers\Api\V1\Lender\Orders\OrderController;
use App\Http\Controllers\Api\V1\Lender\Orders\RejectOrder;
use App\Http\Controllers\Api\V1\Lender\Settings\GetLenderAreaSettings;
use App\Http\Controllers\Api\V1\Lender\Users\UserController;
use App\Http\Controllers\Api\V1\Lender\Wallets\CalculateOrderCost;
use App\Http\Controllers\Api\V1\Lender\Wallets\CreateEdaatInvoice;
use App\Http\Controllers\Api\V1\Lender\Wallets\GetBalance;
use App\Http\Controllers\Api\V1\Lender\Wallets\GetWalletTransactions;
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

Route::prefix('v1/lender')->name('api.v1.')->group(function () {
    Route::get('/area-settings', GetLenderAreaSettings::class);
    Route::post('/register', Register::class);
    Route::post('{user}/complete-register', CompleteRegister::class)->name('lender.complete-register');

    Route::middleware([
        'auth:sanctum',
        'role:'.implode('|', [Role::LenderAdmin, Role::LenderSupervisor, Role::LenderBilling, Role::LenderOrderCreator]),
        'IsEmailVerified:'.Area::Lender,
        InitializeTenancyByRequestData::class,
    ])->group(function () {
        Route::get('auth', GetAuthUser::class);
        Route::get('orders/stats', GetOrdersStats::class);
        Route::apiResource('orders', OrderController::class);
        Route::put('orders/{order}/approve', ApproveOrder::class);
        Route::put('orders/{order}/reject', RejectOrder::class);
        Route::put('orders/{order}/cancel', CancelOrder::class);
        Route::apiResource('users', UserController::class);
        Route::post('{user}/resend-invitation', ResendInvitation::class);

        Route::prefix('wallet')->group(function () {
            Route::get('/balance', GetBalance::class);
            Route::post('/calculate', CalculateOrderCost::class);
            Route::get('/transactions', GetWalletTransactions::class);
            Route::post('/invoice', CreateEdaatInvoice::class);
        });
    });
});

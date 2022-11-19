<?php

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Api\V1\Lender\Auth\CompleteRegister;
use App\Http\Controllers\Api\V1\Lender\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Lender\Auth\Register;
use App\Http\Controllers\Api\V1\Lender\Auth\ResendInvitation;
use App\Http\Controllers\Api\V1\Lender\Auth\UpdateMyProfile;
use App\Http\Controllers\Api\V1\Lender\Enquiries\EnquiryController;
use App\Http\Controllers\Api\V1\Lender\Enquiries\EnquiryReplyController;
use App\Http\Controllers\Api\V1\Lender\Orders\ApproveOrder;
use App\Http\Controllers\Api\V1\Lender\Orders\CancelOrder;
use App\Http\Controllers\Api\V1\Lender\Orders\CreateOrderWithoutVerification;
use App\Http\Controllers\Api\V1\Lender\Orders\GetOrdersStats;
use App\Http\Controllers\Api\V1\Lender\Orders\GetOrdersVolume;
use App\Http\Controllers\Api\V1\Lender\Orders\MakeOrderProceed;
use App\Http\Controllers\Api\V1\Lender\Orders\OrderController;
use App\Http\Controllers\Api\V1\Lender\Orders\RejectOrder;
use App\Http\Controllers\Api\V1\Lender\Settings\GetLenderAreaSettings;
use App\Http\Controllers\Api\V1\Lender\Users\UserController;
use App\Http\Controllers\Api\V1\Lender\Wallets\CalculateOrderCost;
use App\Http\Controllers\Api\V1\Lender\Wallets\CreateEdaatInvoice;
use App\Http\Controllers\Api\V1\Lender\Wallets\GetBalance;
use App\Http\Controllers\Api\V1\Lender\Wallets\GetEdaatInvoices;
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

Route::get('v1/lender/media/{media}/download', DownloadMediaFile::class)->name('api.v1.media.download');

Route::prefix('v1/lender')->name('api.v1.')->group(function () {
    Route::get('/area-settings', GetLenderAreaSettings::class);
    Route::post('/register', Register::class);
    Route::post('{user}/complete-register', CompleteRegister::class)->name('lender.complete-register');

    Route::middleware([
        'auth:sanctum',
        'role:'.implode('|', [Role::LenderAdmin, Role::LenderSupervisor, Role::LenderBilling, Role::LenderOrderCreator, Role::LenderApiUser]),
        InitializeTenancyByRequestData::class,
    ])->group(function () {
        Route::get('auth', GetAuthUser::class);
        Route::get('edaat-invoices', GetEdaatInvoices::class);

        Route::middleware('IsEmailVerified:'.Area::Lender)->group(function () {
            Route::put('auth/profile', UpdateMyProfile::class);
            Route::get('orders/volume', GetOrdersVolume::class);
            Route::get('orders/stats', GetOrdersStats::class);
            Route::apiResource('orders', OrderController::class);
            Route::post('orders/{order}/proceed', MakeOrderProceed::class);
            Route::put('orders/{order}/approve', ApproveOrder::class);
            Route::put('orders/{order}/reject', RejectOrder::class);
            Route::put('orders/{order}/cancel', CancelOrder::class);
            Route::post('orders/no-verification', CreateOrderWithoutVerification::class)
                ->middleware('permission:'.perm(Area::Lender, [Subject::FinancingOrders, Action::Create]));
            Route::apiResource('users', UserController::class);
            Route::post('{user}/resend-invitation', ResendInvitation::class);
            Route::get('edaat-invoices', GetEdaatInvoices::class);

            Route::prefix('wallet')->group(function () {
                Route::get('/balance', GetBalance::class);
                Route::post('/calculate', CalculateOrderCost::class);
                Route::get('/transactions', GetWalletTransactions::class);
                Route::post('/invoice', CreateEdaatInvoice::class);
            });

            Route::apiResource('enquiries', EnquiryController::class);
            Route::apiResource('enquiries.replies', EnquiryReplyController::class)
                ->only('index', 'store');
            Route::apiResource('enquiries.replies', EnquiryReplyController::class);
        });
    });
});

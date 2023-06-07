<?php

use App\Enums\Area;
use App\Enums\Role;
use App\Http\Controllers\Api\V1\Lender\Auth\CompleteRegister;
use App\Http\Controllers\Api\V1\Lender\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Lender\Auth\Register;
use App\Http\Controllers\Api\V1\Lender\Auth\ResendInvitation;
use App\Http\Controllers\Api\V1\Lender\Auth\UpdateMyProfile;
use App\Http\Controllers\Api\V1\Lender\Edaat\EdaatInvoiceController;
use App\Http\Controllers\Api\V1\Lender\Enquiries\EnquiryController;
use App\Http\Controllers\Api\V1\Lender\Enquiries\EnquiryReplyController;
use App\Http\Controllers\Api\V1\Lender\Media\DownloadMediaFile;
use App\Http\Controllers\Api\V1\Lender\Orders\ApproveOrder;
use App\Http\Controllers\Api\V1\Lender\Orders\CancelOrder;
use App\Http\Controllers\Api\V1\Lender\Orders\CompleteOrder;
use App\Http\Controllers\Api\V1\Lender\Orders\CreateOrderWithoutVerification;
use App\Http\Controllers\Api\V1\Lender\Orders\GetOrdersStats;
use App\Http\Controllers\Api\V1\Lender\Orders\GetOrderStatus;
use App\Http\Controllers\Api\V1\Lender\Orders\GetOrdersVolume;
use App\Http\Controllers\Api\V1\Lender\Orders\MakeOrderProceed;
use App\Http\Controllers\Api\V1\Lender\Orders\OrderController;
use App\Http\Controllers\Api\V1\Lender\Orders\RejectOrder;
use App\Http\Controllers\Api\V1\Lender\Orders\UpdateOrderPaymentProof;
use App\Http\Controllers\Api\V1\Lender\Settings\GetLenderAreaSettings;
use App\Http\Controllers\Api\V1\Lender\Settings\SettingsController;
use App\Http\Controllers\Api\V1\Lender\Users\UserController;
use App\Http\Controllers\Api\V1\Lender\Wallets\CalculateOrderCost;
use App\Http\Controllers\Api\V1\Lender\Wallets\GetBalance;
use App\Http\Controllers\Api\V1\Lender\Wallets\GetWalletTransactions;
use App\Http\Controllers\Api\V1\Lender\Webhooks\WebhookController;
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

Route::prefix('v1/lender')->name('api.v1.lender.')->group(function () {
    Route::get('/area-settings', GetLenderAreaSettings::class);
    Route::post('/register', Register::class);

    Route::middleware([
        'auth:sanctum',
        'role:'.implode('|', [
            Role::LenderAdmin, Role::LenderSupervisor, Role::LenderBilling, Role::LenderOrderCreator, Role::LenderApiUser,
        ]),
        InitializeTenancyByRequestData::class,
    ])->group(
        function () {
            Route::get('auth', GetAuthUser::class);
            Route::middleware('verified.email:'.Area::Lender)->group(function () {
                Route::middleware('checkCompanyStatus')->group(function () {
                    Route::put('auth/profile', UpdateMyProfile::class);
                    Route::apiResource('edaat-invoices', EdaatInvoiceController::class)->only('index', 'store');

                    Route::get('orders/volume', GetOrdersVolume::class);
                    Route::get('orders/stats', GetOrdersStats::class);
                    Route::post('orders/no-verification', CreateOrderWithoutVerification::class);
                    Route::get('orders/statuses', GetOrderStatus::class);
                    Route::prefix('orders/{order}')->group(function () {
                        Route::post('/proceed', MakeOrderProceed::class);
                        Route::put('/approve', ApproveOrder::class);
                        Route::put('/reject', RejectOrder::class);
                        Route::post('/complete', CompleteOrder::class);
                        Route::put('/payment-proof', UpdateOrderPaymentProof::class);
                        Route::prefix('/trader-orders/{trader_order}')->group(function () {
                            Route::put('/cancel', CancelOrder::class);
                        });
                    });
                    Route::apiResource('orders', OrderController::class);

                    Route::post('users/{user}/resend-invitation', ResendInvitation::class);
                    Route::apiResource('users', UserController::class);

                    Route::prefix('wallet')->group(
                        function () {
                            Route::get('/balance', GetBalance::class);
                            Route::post('/calculate', CalculateOrderCost::class);
                            Route::get('/transactions', GetWalletTransactions::class);
                        }
                    );

                    Route::apiResource('webhooks', WebhookController::class)->only('index', 'store', 'destroy');
                    Route::put('webhooks/refresh-secret', [WebhookController::class, 'refreshSecret']);

                    Route::get('/settings', [SettingsController::class, 'index']);
                    Route::put('/settings', [SettingsController::class, 'update']);
                });
            });
            Route::apiResource('enquiries', EnquiryController::class)->only(['index', 'show', 'store']);
            Route::apiResource('enquiries.replies', EnquiryReplyController::class)->only('index', 'store')->only(['index', 'store']);
        }
    );

    Route::post('{user}/sign-up', CompleteRegister::class)->name('sign-up');
});

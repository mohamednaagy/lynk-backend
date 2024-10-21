<?php

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\Auth\CompleteAdminRegister;
use App\Http\Controllers\Api\V1\Admin\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Admin\Auth\ResendAdminInvitation;
use App\Http\Controllers\Api\V1\Admin\Auth\UpdateMyProfile;
use App\Http\Controllers\Api\V1\Admin\Commodities\CommodityItemController;
use App\Http\Controllers\Api\V1\Admin\Commodities\CommoditySupplierController;
use App\Http\Controllers\Api\V1\Admin\Commodities\CommoditySupplierUserController;
use App\Http\Controllers\Api\V1\Admin\Commodities\CommodityTypeController;
use App\Http\Controllers\Api\V1\Admin\Commodities\ProductCodeCacheController;
use App\Http\Controllers\Api\V1\Admin\Commodities\ResendInvitationToUserController as ResendSupplierInvitationToUser;
use App\Http\Controllers\Api\V1\Admin\Edaat\GetEdaatInvoices;
use App\Http\Controllers\Api\V1\Admin\Enquiries\EnquiryController;
use App\Http\Controllers\Api\V1\Admin\Enquiries\EnquiryReplyController;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\ApproveOrder;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\ExportOrders;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\LenderTransactionController;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\MakeOrderProceed;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\OrderController;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\RejectOrder;
use App\Http\Controllers\Api\V1\Admin\Images\UploadImage;
use App\Http\Controllers\Api\V1\Admin\Lenders\CalculateAmountWithoutVatAndOrdersCount;
use App\Http\Controllers\Api\V1\Admin\Lenders\ChargeLenderBalanceManually;
use App\Http\Controllers\Api\V1\Admin\Lenders\GetLenderBalance;
use App\Http\Controllers\Api\V1\Admin\Lenders\GetLenderMarketTypes;
use App\Http\Controllers\Api\V1\Admin\Lenders\GetLenderSetting;
use App\Http\Controllers\Api\V1\Admin\Lenders\GetLenderStatuses;
use App\Http\Controllers\Api\V1\Admin\Lenders\LenderController;
use App\Http\Controllers\Api\V1\Admin\Lenders\LenderLiteList;
use App\Http\Controllers\Api\V1\Admin\Lenders\LenderUserController;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\CancelOrder as CancelFinancingOrder;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\CompleteOrder;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\RetryProceedOrder;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrderController;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\CancelTraderOrder;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\GetCommodityCertificateForClient;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\GetMurabahaPurchaseOffer;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\GetMurabhaCompleteDocument;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\GetPurchasingCommodity;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\GetTradersWithAvailableModes;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\LocalMarketWebhook;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\UpdateCommodityCertificateForClient;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\UpdateMurabahaPurchaseOffer;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\UpdateMurabhaCompleteDocument;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\UpdatePurchasingCommodity;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\UpdateSellConfirmationDocument;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\UpdateOrderPaymentProof;
use App\Http\Controllers\Api\V1\Admin\Lenders\ResendInvitationToUser as ResendLenderInvitationToUser;
use App\Http\Controllers\Api\V1\Admin\Lenders\UpdateLenderStatus;
use App\Http\Controllers\Api\V1\Admin\Media\DownloadMedia;
use App\Http\Controllers\Api\V1\Admin\Roles\GetAllPermissions;
use App\Http\Controllers\Api\V1\Admin\Roles\GetAllRoles;
use App\Http\Controllers\Api\V1\Admin\Settings\LenderSettingsController;
use App\Http\Controllers\Api\V1\Admin\Settings\LocalMurabahaSettingsController;
use App\Http\Controllers\Api\V1\Admin\Settings\ProjectSettingsController;
use App\Http\Controllers\Api\V1\Admin\Settings\WakalaTemplateController;
use App\Http\Controllers\Api\V1\Admin\Traders\ResendInvitationToUser as ResendTraderInvitationToUser;
use App\Http\Controllers\Api\V1\Admin\Traders\TraderController;
use App\Http\Controllers\Api\V1\Admin\Traders\TraderUserController;
use App\Http\Controllers\Api\V1\Admin\Traders\UpdateTraderStatus;
use App\Http\Controllers\Api\V1\Lender\Wallets\CheckEdaatInvoiceStatus;
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

Route::prefix('v1/admin')->name('api.v1.admins.')->group(function () {
    Route::middleware(['auth:sanctum', 'role:'.implode('|', [Role::Admin, Role::Manager])])->group(function () {
        Route::get('auth', GetAuthUser::class);
        Route::put('auth/profile', UpdateMyProfile::class);

        Route::post('admins/{admin}/resend-invitation', ResendAdminInvitation::class);
        Route::apiResource('admins', AdminController::class);
        Route::patch('admins/{admin}', [AdminController::class, 'partiallyUpdate']);

        Route::get('/roles', GetAllRoles::class)->middleware(
            'permission:'.perm(Area::SuperAdmin, [Subject::Roles, Action::Index])
        );

        Route::get('/permissions', GetAllPermissions::class)->middleware(
            'permission:'.perm(Area::SuperAdmin, [Subject::Permissions, Action::Index])
        );

        Route::prefix('settings')->group(function () {
            Route::get('/lender', [LenderSettingsController::class, 'index']);
            Route::put('/lender', [LenderSettingsController::class, 'update']);

            Route::get('/project', [ProjectSettingsController::class, 'index']);
            Route::put('/project', [ProjectSettingsController::class, 'update']);

            Route::get('/local-commodity', [LocalMurabahaSettingsController::class, 'index']);
            Route::put('/local-commodity', [LocalMurabahaSettingsController::class, 'update']);
        });

        Route::get('wakala-templates/{type}', [WakalaTemplateController::class, 'index'])
            ->where('type', 'client|company');
        Route::put('wakala-templates/{type}', [WakalaTemplateController::class, 'update'])
            ->where('type', 'client|company');

        Route::prefix('lenders')->group(function () {
            Route::get('/statuses', GetLenderStatuses::class);
            Route::get('/market_types', GetLenderMarketTypes::class);

            Route::get('/dropdown-list', LenderLiteList::class);
            Route::put('/{lender}/status', UpdateLenderStatus::class);
            Route::get('/{lender}/balance ', GetLenderBalance::class);
            Route::get('/{lender}/transactions ', [LenderTransactionController::class, 'index']);
            Route::get('/{lender}/calculate-balance/{amount_with_vat}', CalculateAmountWithoutVatAndOrdersCount::class)
                ->whereNumber('amount');
            Route::post('/{lender}/wallet/manual-deposit', ChargeLenderBalanceManually::class);
            Route::get('/{lender}/settings ', GetLenderSetting::class);
        });

        Route::prefix('lenders')->group(function () {
            Route::post('{lender}/users/{user}/resend-invitation', ResendLenderInvitationToUser::class);
        });

        Route::apiResource('lenders', LenderController::class);
        Route::apiResource('commodity-suppliers', CommoditySupplierController::class);

        Route::apiResource('lenders.users', LenderUserController::class)->scoped();

        Route::apiResource('commodity-types', CommodityTypeController::class);

        Route::get('orders/export', ExportOrders::class);
        Route::post('update-trader/local-market-webhook', LocalMarketWebhook::class);

        Route::prefix('orders/{order}')->group(function () {
            Route::post('trader-orders', [TraderOrderController::class, 'store']);
            Route::put('approve', ApproveOrder::class);
            Route::put('reject', RejectOrder::class);
            Route::post('retry', RetryProceedOrder::class);
            Route::post('complete', CompleteOrder::class);
            Route::put('/cancel', CancelFinancingOrder::class);
            Route::put('payment-proof', UpdateOrderPaymentProof::class);
            Route::prefix('/trader-orders/{trader_order}')->group(function () {
                Route::post('/proceed', MakeOrderProceed::class);
                Route::post('/purchasing-commodity', UpdatePurchasingCommodity::class);
                Route::get('/purchasing-commodity', GetPurchasingCommodity::class);
                Route::post('/murabha-purchase-offer', UpdateMurabahaPurchaseOffer::class);
                Route::get('/murabha-purchase-offer', GetMurabahaPurchaseOffer::class);
                Route::get('/selling-commodity-to-client', GetCommodityCertificateForClient::class);
                Route::post('/selling-commodity-to-client', UpdateCommodityCertificateForClient::class);
                Route::get('/murabha-complete', GetMurabhaCompleteDocument::class);
                Route::post('/murabha-complete', UpdateMurabhaCompleteDocument::class);
                Route::post('/attach-sell-confirmation-document', UpdateSellConfirmationDocument::class);
                Route::put('/cancel', CancelTraderOrder::class);
            });

        });

        Route::apiResource('orders', OrderController::class)
            ->only('index', 'show', 'store', 'update');

        Route::prefix('traders')->group(function () {
            Route::post('{trader}/users/{user}/resend-invitation', ResendTraderInvitationToUser::class);
            Route::put('/{trader}/status', UpdateTraderStatus::class);
        });

        Route::get('/traders-with-modes', GetTradersWithAvailableModes::class);

        Route::get('product-codes', [ProductCodeCacheController::class, 'index']);
        Route::delete('product-codes', [ProductCodeCacheController::class, 'delete']);

        Route::apiResource('traders', TraderController::class)
            ->only(['index', 'store', 'show', 'update']);
        Route::apiResource('traders.users', TraderUserController::class);

        Route::get('edaat-invoices', GetEdaatInvoices::class);
        Route::post('edaat-invoices/{invoice}/check-status', CheckEdaatInvoiceStatus::class);

        Route::apiResource('enquiries', EnquiryController::class)->only(['index', 'show']);
        Route::apiResource('enquiries.replies', EnquiryReplyController::class)->only(['index', 'store']);

        Route::get('media/{media}/download', DownloadMedia::class);

        Route::post('/upload-image', [UploadImage::class, 'store']);

        Route::prefix('commodity-suppliers')->group(function () {
            Route::post('{supplier}/users/{user}/resend-invitation', ResendSupplierInvitationToUser::class);
            Route::apiResource('{supplier}/users', CommoditySupplierUserController::class)->only(['index', 'show', 'store', 'update']);
        });

        Route::prefix('commodity-items')->group(function () {
            Route::get('/', [CommodityItemController::class, 'index']);
        });

    });

    Route::post('/{admin}/sign-up', CompleteAdminRegister::class)->name('admin.sign-up');
});

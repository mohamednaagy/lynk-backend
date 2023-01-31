<?php

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\Auth\CompleteAdminRegister;
use App\Http\Controllers\Api\V1\Admin\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Admin\Auth\UpdateMyProfile;
use App\Http\Controllers\Api\V1\Admin\Edaat\GetEdaatInvoices;
use App\Http\Controllers\Api\V1\Admin\Enquiries\EnquiryController;
use App\Http\Controllers\Api\V1\Admin\Enquiries\EnquiryReplyController;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\LenderOrderController;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\LenderTransactionController;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\MakeOrderProceed;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\TraderOrderController;
use App\Http\Controllers\Api\V1\Admin\Images\UploadImage;
use App\Http\Controllers\Api\V1\Admin\Lenders\ChargeLenderBalanceManually;
use App\Http\Controllers\Api\V1\Admin\Lenders\GetLenderBalance;
use App\Http\Controllers\Api\V1\Admin\Lenders\GetLenderSetting;
use App\Http\Controllers\Api\V1\Admin\Lenders\GetLenderStatuses;
use App\Http\Controllers\Api\V1\Admin\Lenders\LenderController;
use App\Http\Controllers\Api\V1\Admin\Lenders\LenderUserController;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\GetPurchasingCommodity;
use App\Http\Controllers\Api\V1\Admin\Lenders\Orders\TraderOrders\UpdatePurchasingCommodity;
use App\Http\Controllers\Api\V1\Admin\Lenders\UpdateLenderStatus;
use App\Http\Controllers\Api\V1\Admin\Media\DownloadMedia;
use App\Http\Controllers\Api\V1\Admin\Roles\GetAllPermissions;
use App\Http\Controllers\Api\V1\Admin\Roles\GetAllRoles;
use App\Http\Controllers\Api\V1\Admin\Settings\LenderSettingsController;
use App\Http\Controllers\Api\V1\Admin\Settings\ProjectSettingsController;
use App\Http\Controllers\Api\V1\Admin\Settings\WakalaTemplateController;
use App\Http\Controllers\Api\V1\Admin\Traders\TraderController;
use App\Http\Controllers\Api\V1\Admin\Traders\TraderUserController;
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

        Route::apiResource('admins', AdminController::class);

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
        });

        Route::get('wakala-templates/{type}', [WakalaTemplateController::class, 'index'])
            ->where('type', 'client|company');
        Route::put('wakala-templates/{type}', [WakalaTemplateController::class, 'update'])
            ->where('type', 'client|company');

        Route::get('lenders/statuses', GetLenderStatuses::class);

        Route::prefix('lenders')->group(function () {
            Route::put('/{lender}/status', UpdateLenderStatus::class);
            Route::get('/{lender}/balance ', GetLenderBalance::class);
            Route::get('/{lender}/orders/{order}', [LenderOrderController::class, 'show']);
            Route::get('{lender}/orders', [LenderOrderController::class, 'index']);
            Route::get('/{lender}/transactions ', [LenderTransactionController::class, 'index']);
            Route::post('/{lender}/wallet/manual-deposit', ChargeLenderBalanceManually::class);
            Route::get('/{lender}/settings ', GetLenderSetting::class);
            Route::prefix('/{lender}/orders/{order}')->group(function () {
                Route::prefix('/trader-orders/{trader_order}')->group(function () {
                    Route::post('/proceed', MakeOrderProceed::class);
                    Route::post('/purchasing-commodity', UpdatePurchasingCommodity::class);
                    Route::get('/purchasing-commodity', GetPurchasingCommodity::class);
                });
            });
        });

        Route::prefix('traders')->group(function () {
            Route::get('/{trader}/orders/{order}', [TraderOrderController::class, 'show']);
            Route::get('{trader}/orders', [TraderOrderController::class, 'index']);
        });

        Route::apiResource('lenders', LenderController::class);
        Route::apiResource('lenders.users', LenderUserController::class);

        Route::apiResource('traders.users', TraderUserController::class);

        Route::get('edaat-invoices', GetEdaatInvoices::class);
        Route::post('edaat-invoices/{invoice}/check-status', CheckEdaatInvoiceStatus::class);

        Route::apiResource('enquiries', EnquiryController::class)->only(['index', 'show']);
        Route::apiResource('enquiries.replies', EnquiryReplyController::class)->only(['index', 'store']);

        Route::get('media/{media}/download', DownloadMedia::class);

        Route::post('/upload-image', [UploadImage::class, 'store']);

        Route::apiResource('traders', TraderController::class)
            ->only(['index', 'store', 'show', 'update']);
    });

    Route::post('/{admin}/sign-up', CompleteAdminRegister::class)->name('admin.sign-up');
});

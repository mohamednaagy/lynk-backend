<?php

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\Auth\CompleteAdminRegister;
use App\Http\Controllers\Api\V1\Admin\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Admin\Auth\UpdateMyProfile;
use App\Http\Controllers\Api\V1\Admin\Companies\ChargeLenderBalanceManually;
use App\Http\Controllers\Api\V1\Admin\Companies\CompanyController;
use App\Http\Controllers\Api\V1\Admin\Companies\GetCompanyBalance;
use App\Http\Controllers\Api\V1\Admin\Companies\GetCompanySetting;
use App\Http\Controllers\Api\V1\Admin\Companies\GetCompanyStatuses;
use App\Http\Controllers\Api\V1\Admin\Companies\UpdateCompanyStatus;
use App\Http\Controllers\Api\V1\Admin\Companies\UserController;
use App\Http\Controllers\Api\V1\Admin\Edaat\GetEdaatInvoices;
use App\Http\Controllers\Api\V1\Admin\Enquiries\EnquiryController;
use App\Http\Controllers\Api\V1\Admin\Enquiries\EnquiryReplyController;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\FinancingOrderController;
use App\Http\Controllers\Api\V1\Admin\FinancingOrders\FinancingOrderTransactionController;
use App\Http\Controllers\Api\V1\Admin\Images\UploadImage;
use App\Http\Controllers\Api\V1\Admin\Media\DownloadMedia;
use App\Http\Controllers\Api\V1\Admin\Roles\GetAllPermissions;
use App\Http\Controllers\Api\V1\Admin\Roles\GetAllRoles;
use App\Http\Controllers\Api\V1\Admin\Settings\LenderSettingsController;
use App\Http\Controllers\Api\V1\Admin\Settings\ProjectSettingsController;
use App\Http\Controllers\Api\V1\Admin\Settings\WakalaTemplateController;
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
        Route::middleware('verified.email:'.Area::SuperAdmin)
            ->group(function () {
                Route::put('auth/profile', UpdateMyProfile::class);
            });

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

            Route::get('/project', [ProjectSettingsController::class, 'show']);
            Route::put('/project', [ProjectSettingsController::class, 'update']);
        });

        Route::get('wakala-templates/{type}', [WakalaTemplateController::class, 'show'])
            ->where('type', 'client|company');
        Route::put('wakala-templates/{type}', [WakalaTemplateController::class, 'update'])
            ->where('type', 'client|company');

        Route::get('companies/statuses', GetCompanyStatuses::class);

        Route::prefix('companies')->group(function () {
            Route::put('/{company}/status', UpdateCompanyStatus::class);
            Route::get('/{company}/balance ', GetCompanyBalance::class);
            Route::get('/{company}/orders/{order}', [FinancingOrderController::class, 'show']);
            Route::get('{company}/orders', [FinancingOrderController::class, 'index']);
            Route::get('/{company}/transactions ', [FinancingOrderTransactionController::class, 'index']);
            Route::post('/{company}/wallet/manual-deposit', ChargeLenderBalanceManually::class);
            Route::get('/{company}/settings ', GetCompanySetting::class);
        });

        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('companies.users', UserController::class)->shallow();

        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('companies.users', UserController::class)->shallow();

        Route::get('edaat-invoices', GetEdaatInvoices::class);
        Route::post('edaat-invoices/{invoice}/check-status', CheckEdaatInvoiceStatus::class);

        Route::apiResource('enquiries', EnquiryController::class);
        Route::apiResource('enquiries.replies', EnquiryReplyController::class);

        Route::get('media/{media}/download', DownloadMedia::class);

        Route::post('/upload-image', [UploadImage::class, 'store']);
    });

    Route::post('/{admin}/sign-up', CompleteAdminRegister::class)->name('admin.sign-up');
});

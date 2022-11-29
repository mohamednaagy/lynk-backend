<?php

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\Auth\CompleteAdminRegister;
use App\Http\Controllers\Api\V1\Admin\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Admin\Auth\UpdateMyProfile;
use App\Http\Controllers\Api\V1\Admin\Companies\CompanyController;
use App\Http\Controllers\Api\V1\Admin\Companies\GetCompanySetting;
use App\Http\Controllers\Api\V1\Admin\Companies\UpdateCompanyStatus;
use App\Http\Controllers\Api\V1\Admin\Companies\UserController;
use App\Http\Controllers\Api\V1\Admin\Edaat\GetEdaatInvoices;
use App\Http\Controllers\Api\V1\Admin\Enquiries\EnquiryController;
use App\Http\Controllers\Api\V1\Admin\Enquiries\EnquiryReplyController;
use App\Http\Controllers\Api\V1\Admin\Images\UploadImage;
use App\Http\Controllers\Api\V1\Admin\Media\DownloadMedia;
use App\Http\Controllers\Api\V1\Admin\Orders\GetBalance;
use App\Http\Controllers\Api\V1\Admin\Orders\OrderController;
use App\Http\Controllers\Api\V1\Admin\Roles\GetAllPermissions;
use App\Http\Controllers\Api\V1\Admin\Roles\GetAllRoles;
use App\Http\Controllers\Api\V1\Admin\Settings\ProjectSettingsController;
use App\Http\Controllers\Api\V1\Admin\Settings\SettingsController;
use App\Http\Controllers\Api\V1\Admin\Settings\WakalaTemplateController;
use App\Http\Controllers\Api\V1\Admin\Transactions\TransactionController;
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

Route::prefix('v1/admin')->group(function () {
    Route::post('/{admin}/sign-up', CompleteAdminRegister::class)->name('admin.complete-register');

    Route::middleware(['auth:sanctum', 'role:'.Role::Admin])->group(function () {
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
            Route::get('/', [SettingsController::class, 'index']);
            Route::put('/update', [SettingsController::class, 'update']);
            Route::get('/wakala-templates/{type}', [WakalaTemplateController::class, 'show'])
                ->where('type', 'client|company');
            Route::put('/wakala-templates/{type}', [WakalaTemplateController::class, 'update'])
                ->where('type', 'client|company');
            Route::get('/project', [ProjectSettingsController::class, 'show']);
            Route::put('/project', [ProjectSettingsController::class, 'update']);
        });

        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('companies.users', UserController::class)->shallow();

        Route::prefix('companies')->group(function () {
            Route::put('/{company}/status', UpdateCompanyStatus::class);
            Route::get('/{company}/balance ', GetBalance::class);
            Route::get('/{company}/orders/{order}', [OrderController::class, 'show']);
            Route::get('{company}/orders', [OrderController::class, 'index']);
            Route::get('/{company}/transactions ', [TransactionController::class, 'index']);
            Route::get('/{company}/settings ', GetCompanySetting::class);
        });

        Route::prefix('wallet')->group(function () {
            Route::get('/edaat-invoices', GetEdaatInvoices::class);
            Route::post('/edaat-invoices/{invoice}/check-status', CheckEdaatInvoiceStatus::class);
        });

        Route::apiResource('enquiries', EnquiryController::class);
        Route::apiResource('enquiries.replies', EnquiryReplyController::class);

        Route::get('media/{media}/download', DownloadMedia::class);

        Route::post('/upload-Image', [UploadImage::class, 'store']);
    });
});

<?php

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Admin\Companies\CompanyController;
use App\Http\Controllers\Api\V1\Admin\Companies\GetCompanySetting;
use App\Http\Controllers\Api\V1\Admin\Companies\UserController;
use App\Http\Controllers\Api\V1\Admin\Customers\CustomerController;
use App\Http\Controllers\Api\V1\Admin\Edaat\GetEdaatInvoices;
use App\Http\Controllers\Api\V1\Admin\Orders\GetBalance;
use App\Http\Controllers\Api\V1\Admin\Orders\OrderController;
use App\Http\Controllers\Api\V1\Admin\Roles\GetAllPermissions;
use App\Http\Controllers\Api\V1\Admin\Roles\GetAllRoles;
use App\Http\Controllers\Api\V1\Admin\Settings\SettingsController;
use App\Http\Controllers\Api\V1\Admin\Transaction\TransactionController;
use Illuminate\Support\Facades\Route;
use Modules\Grantify\Facades\Grantify;

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

Route::middleware(['auth:sanctum', 'role:'.Role::Admin])->prefix('v1/admin')->group(function () {
    Route::get('auth', GetAuthUser::class);

    Route::apiResource('admins', AdminController::class)->except(['show'])->parameters(['admins' => 'id']);
    Route::apiResource('customers', CustomerController::class)->parameters(['customers' => 'id']);

    Route::get('/roles', GetAllRoles::class)->middleware(
        'permission:'.
            Grantify::transformToPermissionsFormat(Area::SuperAdmin, Subject::Roles, [
                Action::Index,
            ])
    );
    Route::get('/permissions', GetAllPermissions::class)->middleware(
        'permission:'.
            Grantify::transformToPermissionsFormat(Area::SuperAdmin, Subject::Permissions, [
                Action::Index,
            ])
    );

    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/update', [SettingsController::class, 'update']);
    });

    Route::apiResource('companies', CompanyController::class);
    Route::prefix('companies')->group(function () {
        Route::get('/{company}/balance ', GetBalance::class);
        Route::get('/{company}/users', [UserController::class, 'index']);
        Route::get('/{company}/orders/{order}', [OrderController::class, 'show']);
        Route::get('{company}/orders', [OrderController::class, 'index']);
        Route::get('/{company}/transactions ', [TransactionController::class, 'index']);
        Route::get('/{company}/settings ', GetCompanySetting::class);
        Route::put('/{company}/settings', UpdateCompanySettingsController::class);
    });

    Route::get('edaat-invoices', GetEdaatInvoices::class);

    Route::prefix('users')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\V1\Admin\Users\UserController::class, 'store']);
    });
});

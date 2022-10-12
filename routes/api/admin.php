<?php

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Role;
use App\Enums\Subject;
use App\Http\Controllers\Api\V1\Admins\AdminController;
use App\Http\Controllers\Api\V1\Admins\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Admins\Customers\CustomerController;
use App\Http\Controllers\Api\v1\Admins\Lender\LenderController;
use App\Http\Controllers\Api\V1\Admins\Roles\GetAllPermissions;
use App\Http\Controllers\Api\V1\Admins\Roles\GetAllRoles;
use App\Http\Controllers\Api\V1\Admins\Settings\SettingsController;
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
    Route::apiResource('lenders', LenderController::class)->parameters(['lenders' => 'id']);

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
});

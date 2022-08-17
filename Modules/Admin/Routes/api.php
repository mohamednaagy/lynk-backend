<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\Api\GetAuthUser;
use Modules\Admin\Http\Controllers\Api\V1\Admins\AdminController;
use Modules\Admin\Http\Controllers\Api\V1\Customers\CustomerController;
use Modules\Admin\Http\Controllers\Api\V1\Roles\GetAllPermissions;
use Modules\Admin\Http\Controllers\Api\V1\Roles\GetAllRoles;
use Modules\Admin\Http\Controllers\Api\V1\Settings\SettingsController;
use Modules\Permission\Enums\Role;

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

Route::middleware(['auth:api', 'role:' . Role::Admin])->prefix('admin')->group(function () {
    Route::get('/auth', GetAuthUser::class);

    Route::apiResource('admins', AdminController::class)->except(['show'])->parameters(['admins' => 'id']);
    Route::apiResource('customers', CustomerController::class)->parameters(['customers' => 'id']);

    Route::group(['middleware' => ['permission:'.\Grantify::getAuthUserPermissionsForMiddleware()]], function () {
        Route::get('/roles', GetAllRoles::class);
        Route::get('/permissions', GetAllPermissions::class);
    });

    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/update', [SettingsController::class, 'update']);
    });
});

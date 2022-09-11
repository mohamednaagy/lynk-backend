<?php

use App\Enums\Area;
use App\Enums\Role;
use Illuminate\Support\Facades\Route;
use Modules\Permission\Facades\Grantify;
use App\Http\Controllers\Api\Auth\GetAuthUser;
use App\Http\Controllers\Api\V1\Admins\AdminController;
use App\Http\Controllers\Api\V1\Admins\Roles\GetAllRoles;
use App\Http\Controllers\Api\V1\Admins\Roles\GetAllPermissions;
use App\Http\Controllers\Api\V1\Admins\Settings\SettingsController;
use App\Http\Controllers\Api\V1\Admins\Customers\CustomerController;

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

Route::middleware(['auth:sanctum', 'role:' . Role::Admin])->prefix('v1/admin')->group(function () {
    Route::middleware(['authorized:' . Area::SuperAdmin])->group(function () {
        Route::get('/auth', GetAuthUser::class);

        Route::apiResource('admins', AdminController::class)->except(['show'])->parameters(['admins' => 'id']);
        Route::apiResource('customers', CustomerController::class)->parameters(['customers' => 'id']);

        Route::group(['middleware' => ['permission:' . Grantify::getAuthUserPermissionsForMiddleware()]], function () {
            Route::get('/roles', GetAllRoles::class);
            Route::get('/permissions', GetAllPermissions::class);
        });

        Route::prefix('settings')->group(function () {
            Route::get('/', [SettingsController::class, 'index']);
            Route::put('/update', [SettingsController::class, 'update']);
        });
    });
});

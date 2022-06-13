<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\Api\AdminController;
use Modules\Admin\Http\Controllers\Api\CustomerController;
use Modules\Admin\Http\Controllers\Api\GetAllPermissions;
use Modules\Admin\Http\Controllers\Api\GetAllRoles;
use Modules\Admin\Http\Controllers\Api\GetAuthUser;
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

    Route::apiResource('admins', AdminController::class)->except(['show']);

    Route::get('/roles', GetAllRoles::class);
    Route::get('/permissions', GetAllPermissions::class);

    Route::apiResource('customers', CustomerController::class)->parameters(['customers' => 'id']);
});

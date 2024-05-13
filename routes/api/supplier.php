<?php

use App\Enums\Role;
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

Route::prefix('v1/commodity-suppliers')->name('api.v1.commodity-supplier.')->group(function () {
    Route::middleware([
        'auth:sanctum',
        'role:'.implode('|', [
            Role::SupplierAdmin,
            Role::SupplierApiAdmin,

        ]),
    ])->group(function () {
        Route::get('constants', [App\Http\Controllers\Api\V1\Supplier\Constant\ConstantController::class, 'index']);

    });

});

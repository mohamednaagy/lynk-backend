<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Customers\Http\Controllers\api\CustomerController;
use Modules\Customers\Http\Controllers\api\Auth\RegisterController;

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

Route::post('/register', RegisterController::class);

Route::get('/me', [CustomerController::class, 'show']);

Route::middleware('auth:api')->get('/customers', function (Request $request) {
    return $request->user();
});

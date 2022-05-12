<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Customers\Http\Controllers\Api\CustomerAuthController;

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

Route::post('/register', [CustomerAuthController::class, 'register']);

Route::middleware('auth:api')->get('/customers', function (Request $request) {
    return $request->user();
});

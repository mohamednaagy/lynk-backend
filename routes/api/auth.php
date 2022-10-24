<?php

use App\Http\Controllers\Api\v1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\SmsController;
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

Route::post('v1/auth/login', [LoginController::class, 'authenticate']);
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/auth/logout', [LoginController::class, 'logout']);
});

Route::post('v1/auth/send-reset-password-link', ForgotPasswordController::class);
Route::post('v1/auth/reset-password', ResetPasswordController::class);
Route::post('v1/auth/Send-Sms', [SmsController::class, 'sendMessage']);

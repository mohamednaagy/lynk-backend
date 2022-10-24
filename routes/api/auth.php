<?php

use App\Http\Controllers\Api\v1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmail;
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

Route::prefix('v1/auth')->group(function () {
    Route::post('send-reset-password-link', ForgotPasswordController::class);
    Route::post('reset-password', ResetPasswordController::class);
    Route::post('verify-email/{company}/{user}', VerifyEmail::class);
});

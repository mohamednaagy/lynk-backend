<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\ResetPassword;
use App\Http\Controllers\Api\V1\Auth\SendEmailVerification;
use App\Http\Controllers\Api\V1\Auth\SendOtp;
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

Route::middleware('auth:sanctum')->prefix('v1/auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout']);
    Route::post('send-email-verification', SendEmailVerification::class);
});

Route::prefix('v1/auth')->name('api.v1.')->group(function () {
    Route::post('login', [LoginController::class, 'authenticate']);
    Route::post('send-reset-password-link', SendEmailVerification::class);
    Route::post('reset-password', ResetPassword::class);
    Route::post('verify-email/{user}', VerifyEmail::class)->name('verify.email');
    Route::post('send-otp', SendOtp::class)->name('send.otp');
    Route::post('check-otp', SendOtp::class)->name('send.otp');
});

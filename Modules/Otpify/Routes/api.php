<?php

use Illuminate\Support\Facades\Route;
use Modules\Otpify\Http\Controllers\OtpifyController;

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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/generate-otp', [OtpifyController::class, 'generateOtp']);
    Route::post('/verify-otp', [OtpifyController::class, 'verifyOtpCode']);
});

<?php

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

use App\Http\Controllers\Api\V1\Client\AcceptClientWakala;
use App\Http\Controllers\Api\V1\Client\SendOtpClientWakala;
use App\Http\Controllers\Api\V1\Client\VerifyOtpClientWakala;

Route::get('verify/wakala/{order}/{national_id}', SendOtpClientWakala::class)
    ->middleware('signed')
    ->name('verify.client.wakala');

Route::post('verify/wakala/{order}/{national_id}', VerifyOtpClientWakala::class);

Route::post('accept/wakala/{order}', AcceptClientWakala::class);

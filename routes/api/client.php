<?php

use App\Http\Controllers\Api\V1\Client\AcceptClientWakala;
use App\Http\Controllers\Api\V1\Client\Media\DownloadMediaFile;
use App\Http\Controllers\Api\V1\Client\SendOtpClientWakala;
use App\Http\Controllers\Api\V1\Client\VerifyOtpClientWakala;
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

Route::get('v1/client/media/{media}/download', DownloadMediaFile::class)->name('api.v1.client.media.download');

Route::prefix('v1/client')->name('api.v1.')->group(function () {
    Route::post('/wakala/access', SendOtpClientWakala::class)
        ->name('verify.client.wakala');

    Route::post('wakala/verify', VerifyOtpClientWakala::class);

    Route::post('wakala/accept', AcceptClientWakala::class);
});

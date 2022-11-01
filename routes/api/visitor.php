<?php

use App\Http\Controllers\Api\V1\Visitor\Enquiries\CreateVisitorEnquiry;
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

Route::prefix('v1/visitor')->name('api.v1.')->group(function () {
    Route::post('enquiries', CreateVisitorEnquiry::class);
});

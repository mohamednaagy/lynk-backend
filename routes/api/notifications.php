<?php

use App\Http\Controllers\Api\V1\NotificationsController;
use App\Http\Controllers\Api\V1\ReportExportWebhookController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('v1')->group(function () {
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
});

Route::prefix('v1')->group(function () {
    Route::post('report-service/callback', ReportExportWebhookController::class)
        ->middleware('webhook.signature');
});

// Custom authentication for broadcasting that handles JWT
Broadcast::routes([
    'middleware' => ['api', 'auth:api'], // JWT instead of session
]);

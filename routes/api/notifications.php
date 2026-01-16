<?php

use App\Http\Controllers\Api\V1\NotificationsController;
use App\Http\Controllers\Api\V1\OrderListExportWebhookController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('v1')->group(function () {
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::put('notifications/{id}/read', [NotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::put('notifications/read-all', [NotificationsController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
});

Route::prefix('v1')->group(function () {
    Route::post('report-service/callback', OrderListExportWebhookController::class);
});

// Custom authentication for broadcasting that handles JWT
Broadcast::routes([
    'middleware' => ['api', 'auth:api'], // JWT instead of session
]);

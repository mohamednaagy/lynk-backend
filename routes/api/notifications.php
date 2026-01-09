<?php

use App\Http\Controllers\Api\V1\NotificationsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->prefix('v1')->group(function () {
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::put('notifications/{id}/read', [NotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::put('notifications/read-all', [NotificationsController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
});

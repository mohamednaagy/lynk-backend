<?php

use App\Http\Controllers\Api\V1\LocalMarket\Test\LoanController;
use App\Http\Controllers\Api\V1\Test\NotificationsController;
use App\Http\Controllers\Api\V1\Test\ReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/test')->group(function () {
    if (app()->isLocal()) {
        Route::post('loan-coverage', [LoanController::class, 'calculateLoanCoverage']);
        Route::post('reports/generate-supplier-monthly-usage', [ReportsController::class, 'generateSupplierMonthlyUsage']);
        Route::post('notifications/send-in-progress-orders', [NotificationsController::class, 'sendInProgressOrders']);
    }

    // TODO move it to inside the if statement after the release v1.36.0 go to production
    Route::post('notifications/send-wallet-remaining-balance-limit', [NotificationsController::class, 'sendWalletBalanceLimitNotification']);
});

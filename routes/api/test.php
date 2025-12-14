<?php

use App\Http\Controllers\Api\V1\LocalMarket\Test\LoanController;
use App\Http\Controllers\Api\V1\Test\ReportsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/test')->group(function () {
    Route::post('loan-coverage', [LoanController::class, 'calculateLoanCoverage']);
    Route::post('reports/generate-supplier-monthly-usage', [ReportsController::class, 'generateSupplierMonthlyUsage']);
});

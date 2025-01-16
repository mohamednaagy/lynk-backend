<?php

use App\Http\Controllers\Api\V1\LocalMarket\Test\LoanController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/test')->group(function () {
    Route::post('loan-coverage', [LoanController::class, 'calculateLoanCoverage']);
});

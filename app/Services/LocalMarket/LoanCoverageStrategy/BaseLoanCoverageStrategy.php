<?php

namespace App\Services\LocalMarket\LoanCoverageStrategy;

use App\Services\LocalMarket\LoanCoverageStrategy\Contracts\LoanCoverageStrategy;
use Illuminate\Support\Facades\Log;

abstract class BaseLoanCoverageStrategy implements LoanCoverageStrategy
{
    protected const GREEDY_STRATEGY = 'greedy';

    protected const OPTIMIZED_STRATEGY = 'optimized';

    protected int $maxUnitsPerTrader;

    protected float $loanCoverageTimeout;

    public function __construct()
    {
        $this->maxUnitsPerTrader = config('trader.providers.lynk.max_units_per_trader', 10000);
        $this->loanCoverageTimeout = config('trader.providers.lynk.loan_coverage_timeout', 3);
    }

    protected function logInfo(string $message, array $data = []): void
    {
        Log::channel('local_market')->info($message, $data);
    }

    protected function getElapsedTime($startTime)
    {
        $endTime = microtime(true);

        return round(($endTime - $startTime), 4);
    }
}

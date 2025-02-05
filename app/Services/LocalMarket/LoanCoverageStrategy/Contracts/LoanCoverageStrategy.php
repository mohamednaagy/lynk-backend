<?php

namespace App\Services\LocalMarket\LoanCoverageStrategy\Contracts;

interface LoanCoverageStrategy
{
    public function calculateCombination(int $loanAmount, array $inventories): array;
}

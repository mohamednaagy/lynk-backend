<?php

namespace App\Services\LocalMarket\LoanCoverageStrategy\Contracts;

use App\Models\LocalMarketOrder;

interface LoanCoverageStrategy
{
    public function calculateCombination(LocalMarketOrder $localMarketOrder, array $inventories): array;
}

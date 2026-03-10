<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\FinancingOrder;

interface FinancingOrderActivityUpdate
{
    /**
     * Update the latest activity for a financing order
     */
    public function updateFinancingOrderLatestActivity(FinancingOrder $financingOrder): void;

    public function getLatestActivityDescription(FinancingOrder $financingOrder): string;
}

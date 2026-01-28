<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\FinancingOrder;

interface FinancingOrderActivityUpdate
{
    /**
     * Get the latest activity description based on status and current step
     */
    public function getLatestActivityDescription(FinancingOrder $financingOrder): string;

    /**
     * Update the latest activity for a financing order
     */
    public function updateFinancingOrderLatestActivity(FinancingOrder $financingOrder): void;
}

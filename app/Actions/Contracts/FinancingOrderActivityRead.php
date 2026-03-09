<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\FinancingOrder;

interface FinancingOrderActivityRead
{
    public function getLatestActivityDescription(FinancingOrder $financingOrder): string;
}

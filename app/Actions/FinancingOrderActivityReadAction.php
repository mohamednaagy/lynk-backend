<?php

namespace App\Actions;

use App\Actions\Contracts\FinancingOrderActivityRead;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Support\Traits\Localizable;

class FinancingOrderActivityReadAction implements FinancingOrderActivityRead
{
    use Localizable;

    /**
     * Get the latest activity description based on status and current step
     */
    public function getLatestActivityDescription(FinancingOrder $financingOrder): string
    {
        return $this->withLocale('en', function () use ($financingOrder) {
            return $financingOrder->status->isNot(FinancingOrderStatus::InProgress)
            || is_null($financingOrder->current_step)
                ? $financingOrder->status->description
                : $financingOrder->current_step->description;
        });
    }
}

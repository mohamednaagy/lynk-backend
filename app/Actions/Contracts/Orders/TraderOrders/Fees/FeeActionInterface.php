<?php

namespace App\Actions\Contracts\Orders\TraderOrders\Fees;

use App\Models\TraderOrder;

/**
 * Common interface for all fee actions.
 *
 * This interface ensures that all fee actions follow the same contract,
 * making them interchangeable and following the Liskov Substitution Principle.
 */
interface FeeActionInterface
{
    /**
     * Handle the fee application for a trader order.
     *
     * @param  TraderOrder  $traderOrder  The trader order to apply fees to
     */
    public function handle(TraderOrder $traderOrder): void;
}

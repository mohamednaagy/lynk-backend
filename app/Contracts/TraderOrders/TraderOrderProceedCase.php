<?php

namespace App\Contracts\TraderOrders;

use App\Models\TraderOrder;

interface TraderOrderProceedCase
{
    /**
     * Validate if the trader order can proceed with the specific case.
     *
     * @throws \App\Exceptions\OrderStatusDoesNotFollowSequenceException
     */
    public function canProceed(TraderOrder $traderOrder, bool $forceToProceed = false): bool;
}

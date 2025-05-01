<?php

namespace App\Support\Traders\Contracts;

use App\Models\TraderOrder;

interface Deliverable
{
    public function validateDeliverySequence(TraderOrder $traderOrder, bool $forceToProceed): void;

    public function confirmDelivery(TraderOrder $traderOrder): bool;
}

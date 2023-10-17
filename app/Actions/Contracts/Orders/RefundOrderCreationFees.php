<?php

namespace App\Actions\Contracts\Orders;

use App\Models\TraderOrder;

interface RefundOrderCreationFees
{
    public function handle(TraderOrder $traderOrder, int $refundReason = null);
}

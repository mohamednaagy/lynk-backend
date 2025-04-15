<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

interface AutoCompleteSell
{
    public function handle(int $traderOrderId, int $periodId): void;
}

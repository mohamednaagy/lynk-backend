<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;

interface SendSmsWhenStatusIsMurabahaSaleCompleted
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void;
}

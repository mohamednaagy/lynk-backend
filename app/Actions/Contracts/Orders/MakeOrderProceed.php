<?php

namespace App\Actions\Contracts\Orders;

interface MakeOrderProceed
{
    public function handle(int $order, string $case);
}

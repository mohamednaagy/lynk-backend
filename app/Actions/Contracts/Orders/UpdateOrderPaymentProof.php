<?php

namespace App\Actions\Contracts\Orders;

interface UpdateOrderPaymentProof
{
    public function handle($financingOrderId, array $data);
}

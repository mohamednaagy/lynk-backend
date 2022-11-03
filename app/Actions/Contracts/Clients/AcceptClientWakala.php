<?php

namespace App\Actions\Contracts\Clients;

use App\Models\FinancingOrder;

interface AcceptClientWakala
{
    /**
     * @param  FinancingOrder  $order
     * @param  string  $token
     * @return bool
     */
    public function handle(FinancingOrder $order, string $token): bool;
}

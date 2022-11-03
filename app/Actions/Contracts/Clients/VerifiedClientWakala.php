<?php

namespace App\Actions\Contracts\Clients;

use App\Models\FinancingOrder;

interface VerifiedClientWakala
{
    /**
     * @param  FinancingOrder  $order
     * @return array
     */
    public function handle(FinancingOrder $order): array;
}

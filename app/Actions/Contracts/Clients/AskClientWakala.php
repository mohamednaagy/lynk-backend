<?php

namespace App\Actions\Contracts\Clients;

use App\Models\FinancingOrder;

interface AskClientWakala
{
    /**
     * @param  FinancingOrder  $order
     * @param  string  $url
     * @return bool
     */
    public function handle(FinancingOrder $order, string $url): bool;
}

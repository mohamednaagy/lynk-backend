<?php

namespace App\Actions\Contracts\Clients;

use App\Models\FinancingOrder;

interface AskClientWakala
{
    public function handle(FinancingOrder $order, string $url): bool;
}

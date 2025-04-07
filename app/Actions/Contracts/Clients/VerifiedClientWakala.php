<?php

namespace App\Actions\Contracts\Clients;

use App\Models\FinancingOrder;

interface VerifiedClientWakala
{
    public function handle(FinancingOrder $order): array;
}

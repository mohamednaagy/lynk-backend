<?php

namespace App\Actions\Contracts\Wakala;

use App\Models\FinancingOrder;

interface GenerateClientWakala
{
    public function handle(FinancingOrder $financingOrder);
}

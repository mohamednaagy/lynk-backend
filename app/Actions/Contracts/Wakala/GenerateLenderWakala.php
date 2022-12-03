<?php

namespace App\Actions\Contracts\Wakala;

use App\Models\FinancingOrder;

interface GenerateLenderWakala
{
    public function handle(FinancingOrder $financingOrder);
}

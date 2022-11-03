<?php

namespace App\Actions\Contracts\Wakala;

use App\Models\FinancingOrder;

interface GenerateWakala
{
    public function handle(FinancingOrder $financingOrder);
}

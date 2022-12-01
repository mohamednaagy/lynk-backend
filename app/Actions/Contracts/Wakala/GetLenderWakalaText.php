<?php

namespace App\Actions\Contracts\Wakala;

use App\Models\FinancingOrder;

interface GetLenderWakalaText
{
    public function handle(FinancingOrder $financingOrder, string $template);
}

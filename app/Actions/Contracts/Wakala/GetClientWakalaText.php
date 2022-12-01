<?php

namespace App\Actions\Contracts\Wakala;

use App\Models\FinancingOrder;

interface GetClientWakalaText
{
    public function handle(FinancingOrder $financingOrder, string $template);
}

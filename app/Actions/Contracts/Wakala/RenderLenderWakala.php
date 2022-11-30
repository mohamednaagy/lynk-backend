<?php

namespace App\Actions\Contracts\Wakala;

use App\Models\FinancingOrder;

interface RenderLenderWakala
{
    public function handle(FinancingOrder $financingOrder, string $template);
}

<?php

namespace App\Actions\Contracts\Wakala;

use App\Models\TraderOrder;

interface GenerateClientWakala
{
    public function handle(TraderOrder $traderOrder);
}

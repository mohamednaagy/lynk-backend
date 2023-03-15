<?php

namespace App\Actions\Contracts\Wakala;

use App\Models\TraderOrder;

interface GetClientWakalaText
{
    public function handle(TraderOrder $traderOrder, string $template);
}

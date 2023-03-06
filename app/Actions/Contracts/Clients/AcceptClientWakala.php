<?php

namespace App\Actions\Contracts\Clients;

use App\Models\TraderOrder;
use App\Models\TraderOrder;

interface AcceptClientWakala
{
    public function handle(TraderOrder $traderOrder): void;
}

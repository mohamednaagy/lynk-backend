<?php

namespace App\Actions\Contracts\Orders;

use App\Models\TraderOrder;

interface LocalMarketWebhook
{
    public function handle(
        TraderOrder $traderOrder,
        array $data,
    ): void;
}

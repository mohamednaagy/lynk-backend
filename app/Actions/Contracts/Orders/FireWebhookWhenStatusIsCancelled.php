<?php

namespace App\Actions\Contracts\Orders;

use App\Models\TraderOrder;

interface FireWebhookWhenStatusIsCancelled
{
    public function handle(TraderOrder $traderOrder): void;
}

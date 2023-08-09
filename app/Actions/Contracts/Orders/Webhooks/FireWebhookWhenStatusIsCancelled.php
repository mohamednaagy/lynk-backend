<?php

namespace App\Actions\Contracts\Orders\Webhooks;

use App\Models\TraderOrder;

interface FireWebhookWhenStatusIsCancelled
{
    public function handle(TraderOrder $traderOrder): void;
}

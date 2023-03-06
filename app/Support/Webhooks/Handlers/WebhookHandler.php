<?php

namespace App\Support\Webhooks\Handlers;

use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class WebhookHandler extends ProcessWebhookJob
{
    public function handle()
    {
        Log::debug('test success webhooks');
        Log::debug($this->webhookCall);
    }
}

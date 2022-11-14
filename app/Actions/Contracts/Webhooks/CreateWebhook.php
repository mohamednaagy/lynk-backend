<?php

namespace App\Actions\Contracts\Webhooks;

use App\Models\Webhook;

interface CreateWebhook
{
    public function handle(array $data): Webhook;
}

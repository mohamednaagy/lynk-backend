<?php

namespace App\Actions\Contracts;

use App\Models\Webhook;

interface CreateWebhook
{
    public function handle(array $data): Webhook;
}

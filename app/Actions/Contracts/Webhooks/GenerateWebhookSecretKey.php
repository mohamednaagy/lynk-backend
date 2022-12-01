<?php

namespace App\Actions\Contracts\Webhooks;

interface GenerateWebhookSecretKey
{
    public function handle(): string;
}

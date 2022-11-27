<?php

namespace App\Actions\Webhooks;

use App\Actions\Contracts\Webhooks\GenerateWebhookSecretKey;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class GenerateWebhookSecretKeyAction implements GenerateWebhookSecretKey
{
    /**
     * @return mixed
     */
    public function handle(): string
    {
        return Str::random(Config::get('webhook-server.secret_key_length', 40));
    }
}

<?php

namespace App\Actions\Webhooks;

use App\Actions\Contracts\Webhooks\GenerateWebhookSecretKey;
use Illuminate\Support\Str;

class GenerateWebhookSecretKeyAction implements GenerateWebhookSecretKey
{
    /**
     * @return mixed
     */
    public function handle(): string
    {
        return Str::random(40);
    }
}

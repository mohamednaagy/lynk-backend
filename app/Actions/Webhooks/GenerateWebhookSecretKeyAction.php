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
        return 'secret-key:'.Str::random(40).':'.now()->toString();
    }
}

<?php

namespace App\Actions;

use App\Actions\Contracts\CreateWebhook;
use App\Models\Webhook;
use Illuminate\Support\Str;

class CreateWebhookAction implements CreateWebhook
{
    /**
     * @return mixed
     */
    public function handle($data): Webhook
    {
        $data['webhook_secret_key'] = base64_encode(Str::random(10));

        return Webhook::create($data);
    }
}
